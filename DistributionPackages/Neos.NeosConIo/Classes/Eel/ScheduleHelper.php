<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Eel;

/*
 * This file is part of the Neos.NeosConIo package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ThumbnailConfiguration;
use Neos\Media\Domain\Service\AssetService;
use Neos\Media\Domain\Service\ThumbnailService;
use Neos\Neos\Domain\NodeLabel\NodeLabelGeneratorInterface;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\Options;

/**
 * Eel helper providing schedule-related data transformations for the NeosCon JSON API endpoint.
 */
class ScheduleHelper implements ProtectedContextAwareInterface
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected NodeLabelGeneratorInterface $nodeLabelGenerator;

    #[Flow\Inject]
    protected NodeUriBuilderFactory $nodeUriBuilderFactory;

    #[Flow\Inject]
    protected ThumbnailService $thumbnailService;

    #[Flow\Inject]
    protected ResourceManager $resourceManager;

    #[Flow\Inject]
    protected AssetService $assetService;

    /**
     * Groups topic nodes (Talks + Breaks) by the date portion of their talkDate property.
     *
     * Returns an ordered array of arrays, each inner array containing the aggregateId
     * strings for all topics of one conference day, sorted chronologically within the day.
     *
     * @param Node[] $topics All topic nodes (Neos.NeosConIo:Talk + Neos.NeosConIo:BreakInSchedule)
     * @return array<int, array<int, string>>
     */
    public function topicsPerDay(array $topics): array
    {
        $days = [];
        foreach ($topics as $topic) {
            if (!$topic instanceof Node) {
                continue;
            }
            $talkDate = $topic->properties['talkDate'] ?? null;
            if (!$talkDate instanceof \DateTimeInterface) {
                continue;
            }
            $dateKey = $talkDate->format('Y-m-d');
            $days[$dateKey][] = $topic;
        }

        ksort($days);

        return array_values(array_map(
            static function (array $dayTopics): array {
                usort($dayTopics, static function (Node $a, Node $b): int {
                    $dateA = $a->properties['talkDate'] ?? null;
                    $dateB = $b->properties['talkDate'] ?? null;
                    if (!$dateA instanceof \DateTimeInterface || !$dateB instanceof \DateTimeInterface) {
                        return 0;
                    }
                    return $dateA <=> $dateB;
                });
                return array_values(array_map(
                    static fn(Node $topic): string => $topic->aggregateId->value,
                    $dayTopics
                ));
            },
            $days
        ));
    }

    /**
     * Indexes an array of nodes into an associative array keyed by their aggregateId value.
     * Duplicate nodes (same aggregateId) are deduplicated, last write wins.
     *
     * @param Node[] $nodes
     * @return array<string, Node>
     */
    public function indexById(array $nodes): array
    {
        $result = [];
        foreach ($nodes as $node) {
            if (!$node instanceof Node) {
                continue;
            }
            $result[$node->aggregateId->value] = $node;
        }
        return $result;
    }

    /**
     * Builds the complete topicsById dictionary for the JSON response.
     *
     * Room names and speaker IDs are resolved directly via the ContentRepository subgraph,
     * mirroring the approach used in groupSpeakerTalksByEvent.
     *
     * @param Node[] $topics All topic nodes (Talks + Breaks)
     * @return array<string, array<string, mixed>>
     */
    public function buildTopicsById(array $topics): array
    {
        $topics = array_filter($topics);
        if (!$topics) {
            return [];
        }

        $firstTopic = $topics[0];
        $nodeTypeManager = $this->contentRepositoryRegistry->get($firstTopic->contentRepositoryId)->getNodeTypeManager();
        $talkNodeType = $nodeTypeManager->getNodeType('Neos.NeosConIo:Talk');
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($firstTopic);

        $result = [];
        foreach ($topics as $topic) {
            $id = $topic->aggregateId->value;
            $isTalk = $nodeTypeManager->getNodeType($topic->nodeTypeName)->isOfType($talkNodeType->name);
            $talkDate = $topic->properties['talkDate'] ?? null;
            $rawText = $topic->properties['text'] ?? '';

            $stage = '';
            $speakerIds = [];

            if ($isTalk) {
                // Resolve the single room reference → stage name
                $roomNode = $subgraph->findReferences(
                    $topic->aggregateId,
                    FindReferencesFilter::create(referenceName: 'room')
                )->getNodes()->first();
                $stage = $roomNode?->properties['name'] ?? '';

                // Resolve all speaker references → list of aggregateId strings
                $speakerIds[] = $subgraph->findReferences(
                    $topic->aggregateId,
                    FindReferencesFilter::create(referenceName: 'speakers')
                )->getNodes()->map(static fn(Node $node) => $node->aggregateId->value);
            }

            $result[$id] = [
                'id'          => $id,
                'title'       => $this->nodeLabelGenerator->getLabel($topic),
                'description' => $isTalk ? strip_tags($rawText) : $rawText,
                'type'        => $isTalk
                                    ? 'TALK'
                                    : $topic->properties['type'] ?? 'BREAK',
                'start'       => $talkDate instanceof \DateTimeInterface
                                    ? $talkDate->format('G:i')
                                    : '',
                'stage'       => $stage,
                'speakerIds'  => $speakerIds,
            ];
        }
        return $result;
    }

    /**
     * Builds the complete speakersById dictionary for the JSON response.
     *
     * Avatar thumbnails are generated via ThumbnailService (600×600 max, same as the
     * Neos.Neos:ImageUri call it replaces). Speaker topics are resolved by delegating
     * to groupSpeakerTalksByEvent internally.
     *
     * @param Node[]        $speakers       All speaker nodes for this event
     * @param ActionRequest $actionRequest  Needed for absolute talk URI generation
     * @return array<string, array<string, mixed>>
     */
    public function buildSpeakersById(array $speakers, ActionRequest $actionRequest): array
    {
        $thumbnailConfiguration = new ThumbnailConfiguration(
            null,
            600,
            null,
            600,
        );
        $result = [];
        foreach ($speakers as $speaker) {
            if (!$speaker instanceof Node) {
                continue;
            }

            $id = $speaker->aggregateId->value;

            // Generate a 600×600 thumbnail URI, falling back to '' when no image is set
            $avatarUri = '';
            $image = $speaker->properties['image'] ?? null;
            if ($image instanceof AssetInterface) {
                try {
                    $thumbnailData = $this->assetService->getThumbnailUriAndSizeForAsset($image, $thumbnailConfiguration, $actionRequest);
                    $avatarUri = $thumbnailData['src'] ?? null;
                } catch (\Exception) {
                    // Leave avatarUri as empty on any failure
                }
            }

            $result[$id] = [
                'id'      => $id,
                'name'    => $speaker->properties['title'] ?? '',
                'avatar'  => $avatarUri,
                'facts'   => [
                    'company'  => $speaker->properties['company'] ?? '',
                    'role'     => $speaker->properties['position'] ?? '',
                    'twitter'  => $speaker->properties['twitter'] ?? '',
                    'github'   => $speaker->properties['github'] ?? '',
                    'mastodon' => $speaker->properties['mastodon'] ?? '',
                ],
                'summary' => strip_tags($speaker->properties['text'] ?? ''),
                'topics'  => $this->groupSpeakerTalksByEvent($speaker, $actionRequest),
            ];
        }
        return $result;
    }

    /**
     * Groups a speaker's talks by the title of the event they belong to.
     *
     * Returns an associative array whose keys are event titles and whose values are
     * sequential arrays of talk aggregateId strings, e.g.:
     *   ['Neos Conference 2026' => ['uuid-a', 'uuid-b'], 'Neos Conference 2025' => ['uuid-c']]
     *
     * @return array<string, array<int, string>>
     */
    public function groupSpeakerTalksByEvent(?Node $speaker, ActionRequest $actionRequest): array
    {
        if (!$speaker) {
            return [];
        }

        $uriBuilder = $this->nodeUriBuilderFactory->forActionRequest($actionRequest);

        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($speaker);
        $speakerTalks = $subgraph->findBackReferences(
            $speaker->aggregateId,
            FindBackReferencesFilter::create(nodeTypes: 'Neos.NeosConIo:Talk', referenceName: 'speakers')
        )->getNodes();

        $result = [];
        foreach ($speakerTalks as $talk) {
            $event = $subgraph->findReferences(
                $talk->aggregateId,
                FindReferencesFilter::create(nodeTypes: 'Neos.NeosConIo:Event', referenceName: 'event')
            )->getNodes()->first();

            // Ignore talks without events
            if (!$event) {
                continue;
            }

            try {
                $talkUri = $uriBuilder->uriFor(NodeAddress::fromNode($talk), Options::createForceAbsolute());
            } catch (\Exception) {
                $talkUri = null; // Fallback to null if URI generation fails
            }

            $result[$talk->aggregateId->value] = [
                'id' => $talk->aggregateId->value,
                'title' => $this->nodeLabelGenerator->getLabel($talk),
                'event' => $this->nodeLabelGenerator->getLabel($event),
                'url' => $talkUri,
                'hasVideo' => (bool)($talk->properties['video'] ?? false),
            ];
        }
        return $result;
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
