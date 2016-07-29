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
        $user = false;

        if (count($arguments) > 0 && is_string($arguments[0])) {
            $user = $this->apiConnector->fetchUser($arguments[0]);
        }

        if ($user) {
            $groups = $this->apiConnector->fetchGroups();
            $user['memberships'] = array_filter($groups, function ($group) use ($user) {
                return in_array($user['name'], $group['members']) && isset($group['neos_group_type']) && !empty($group['neos_group_type']);
            });

            $user['additionalProperties'] = array_filter($user, function ($key) {
                return strpos($key, 'neos_') === 0;
            }, ARRAY_FILTER_USE_KEY);
        }

        $flowQuery->setContext($user);
    }
}
