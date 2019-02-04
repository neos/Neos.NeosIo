<?php

namespace Neos\NeosIo\TypoScript\FlowQueryOperations;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\NeosIo\Service\FundingApiConnector;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

class FundingBadgesOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'fundingBadges';

    /**
     * @Flow\Inject
     *
     * @var FundingApiConnector
     */
    protected $apiConnector;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     *
     * @return bool TRUE if the operation can be applied onto the $context, FALSE otherwise
     */
    public function canEvaluate($context)
    {
        return true;
    }

    /**
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $result = $this->apiConnector->fetchBadges();

        $flowQuery->setContext($result);
    }
}
