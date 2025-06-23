<?php
declare(strict_types=1);

namespace Neos\NeosIo\Fusion\FlowQueryOperations;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\NeosIo\Service\FundingApiConnector;

class FundingBadgesOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'fundingBadges';

    #[Flow\Inject]
    protected FundingApiConnector $apiConnector;

    /**
     * @param array{} $arguments
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $result = $this->apiConnector->fetchBadges();

        $flowQuery->setContext($result);
    }
}
