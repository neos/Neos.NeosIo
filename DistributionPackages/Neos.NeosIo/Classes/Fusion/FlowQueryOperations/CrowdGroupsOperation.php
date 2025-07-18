<?php
declare(strict_types=1);

namespace Neos\NeosIo\Fusion\FlowQueryOperations;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\NeosIo\Service\CrowdApiConnector;

class CrowdGroupsOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'crowdGroups';

    #[Flow\Inject]
    protected CrowdApiConnector $apiConnector;

    /**
     * @param array<string|int, mixed> $arguments
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $groups = $this->apiConnector->fetchGroups();
        if (!$groups) {
            $flowQuery->setContext([]);
            return;
        }
        $flowQuery->setContext($groups);
    }
}
