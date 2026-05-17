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

use GuzzleHttp\Psr7\Uri;
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
use Neos\NeosConIo\Domain\Dto\RelatedTalk;
use Neos\NeosConIo\Domain\Dto\RelatedTalks;
use Neos\NeosConIo\Domain\Dto\Speaker;
use Neos\NeosConIo\Domain\Dto\Speakers;
use Neos\NeosConIo\Domain\Dto\Talk;
use Neos\NeosConIo\Domain\Dto\Talks;

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
     * @return string[][] Array of days, with list of talk ids for that day sorted by their time
     */
    public function topicsPerDay(array $topics): array
    {
        $days = [];
        foreach ($topics as $topic) {
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
                return array_map(
                    static fn(Node $topic): string => $topic->aggregateId->value,
                    $dayTopics
                );
            },
            $days
        ));
    }

    /**
     * Builds the complete topicsById dictionary for the JSON response.
     *
     * Room names and speaker IDs are resolved directly via the ContentRepository subgraph,
     * mirroring the approach used in groupSpeakerTalksByEvent.
     *
     * @param null|Node[] $topics All topic nodes (Talks + Breaks)
     */
    public function buildTopicsById(?array $topics): Talks
    {
        if (!$topics) {
            return Talks::empty();
        }
        $firstTopic = $topics[0];
        $nodeTypeManager = $this->contentRepositoryRegistry->get($firstTopic->contentRepositoryId)->getNodeTypeManager();
        $talkNodeType = $nodeTypeManager->getNodeType('Neos.NeosConIo:Talk');
        if (!$talkNodeType) {
            throw new \RuntimeException('No Neos.NeosConIo:TalkNodeType found', 1779004415);
        }
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($firstTopic);

        $result = [];
        foreach ($topics as $topic) {
            $id = $topic->aggregateId;
            $topicNodeType = $nodeTypeManager->getNodeType($topic->nodeTypeName);
            if (!$topicNodeType) {
                continue;
            }
            $isTalk = $topicNodeType->isOfType($talkNodeType->name);
            $talkDate = $topic->properties['talkDate'] ?? null;
            $rawText = $topic->properties['text'] ?? '';
            $stage = '';
            $speakers = null;

            if (!$talkDate instanceof \DateTimeInterface) {
                continue;
            }

            if ($isTalk) {
                // Resolve the single room reference → stage name
                $roomNode = $subgraph->findReferences(
                    $topic->aggregateId,
                    FindReferencesFilter::create(referenceName: 'room')
                )->getNodes()->first();
                $stage = $roomNode?->properties['name'] ?? '';

                // Resolve all speaker references → list of aggregateId strings
                $speakers = $subgraph->findReferences(
                    $topic->aggregateId,
                    FindReferencesFilter::create(referenceName: 'speakers')
                )->getNodes();
            }

            $result[$id->value] = new Talk(
                $id,
                $this->nodeLabelGenerator->getLabel($topic),
                trim(strip_tags($rawText)),
                $isTalk ? 'TALK' : $topic->properties['type'] ?? 'BREAK',
                $talkDate,
                $stage,
                $speakers,
            );
        }
        return Talks::fromArray($result);
    }

    /**
     * Builds the complete speakersById dictionary for the JSON response.
     *
     * Avatar thumbnails are generated via ThumbnailService (600×600 max, same as the
     * Neos.Neos:ImageUri call it replaces). Speaker topics are resolved by delegating
     * to groupSpeakerTalksByEvent internally.
     *
     * @param Node[] $speakers All speaker nodes for this event
     * @param ActionRequest $actionRequest Needed for absolute talk URI generation
     */
    public function buildSpeakersById(array $speakers, ActionRequest $actionRequest): Speakers
    {
        $thumbnailConfiguration = new ThumbnailConfiguration(
            null,
            600,
            null,
            600,
        );
        $result = [];
        foreach ($speakers as $speaker) {
            $id = $speaker->aggregateId;
            $name = trim($speaker->properties['title'] ?? '');

            if (!$name) {
                continue;
            }

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

            $result[$id->value] = new Speaker(
                $id,
                $name,
                trim(strip_tags($speaker->properties['text'] ?? '')),
                $avatarUri ? new Uri($avatarUri) : null,
                $this->groupSpeakerTalksByEvent($speaker, $actionRequest),
                $speaker->properties['company'] ?? '',
                $speaker->properties['position'] ?? '',
                $speaker->properties['twitter'] ?? '',
                $speaker->properties['github'] ?? '',
                $speaker->properties['mastodon'] ?? '',
            );
        }
        return Speakers::fromArray($result);
    }

    /**
     * Groups a speaker's talks by talk id with the talks title, event, url and video flag.
     */
    public function groupSpeakerTalksByEvent(?Node $speaker, ActionRequest $actionRequest): RelatedTalks
    {
        if (!$speaker) {
            return RelatedTalks::empty(); // Return empty object for null speaker to avoid JSON serialization as empty array
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

            $result[$talk->aggregateId->value] = new RelatedTalk(
                $talk->aggregateId,
                $this->nodeLabelGenerator->getLabel($talk),
                $this->nodeLabelGenerator->getLabel($event),
                $talkUri,
                (bool)($talk->properties['video'] ?? false),
            );
        }
        return RelatedTalks::fromArray($result);
    }

    public function allowsCallOfMethod($methodName): bool
    {
        return true;
    }
}
