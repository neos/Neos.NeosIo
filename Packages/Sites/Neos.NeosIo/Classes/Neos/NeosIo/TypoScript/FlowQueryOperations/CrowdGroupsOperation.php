<?php

namespace Neos\NeosIo\TypoScript\FlowQueryOperations;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\NeosIo\Service\CrowdApiConnector;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;

class CrowdGroupsOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'crowdGroups';

    /**
     * @Flow\Inject
     *
     * @var CrowdApiConnector
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
        $groups = $this->apiConnector->fetchGroups();
        $flowQuery->setContext($groups);
    }
}
