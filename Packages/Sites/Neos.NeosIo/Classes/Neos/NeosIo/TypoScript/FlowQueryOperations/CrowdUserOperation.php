<?php

namespace Neos\NeosIo\TypoScript\FlowQueryOperations;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\NeosIo\Service\CrowdApiConnector;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;

class CrowdUserOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'crowdUser';

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
        $result = false;
        if (count($arguments) > 0 && is_string($arguments[0])) {
            $result = $this->apiConnector->fetchUser($arguments[0]);
        }

        $flowQuery->setContext($result);
    }
}
