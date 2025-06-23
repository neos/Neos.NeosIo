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

class CrowdUserOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'crowdUser';

    #[Flow\Inject]
    protected CrowdApiConnector $apiConnector;

    /**
     * @param array{0?: string|null} $arguments
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $user = false;

        if (count($arguments) > 0 && is_string($arguments[0])) {
            $user = $this->apiConnector->fetchUser($arguments[0]);
        }

        if (!$user) {
            return;
        }
        $groups = $this->apiConnector->fetchGroups();
        if ($groups) {
            $user['memberships'] = array_filter($groups, static function ($group) use ($user) {
                return in_array($user['name'], $group['members'], true) && !empty($group['neos_group_type']);
            });
        }

        $user['additionalProperties'] = array_filter(
            $user,
            static fn ($key) => str_starts_with($key, 'neos_'), ARRAY_FILTER_USE_KEY
        );
        $flowQuery->setContext($user);
    }
}
