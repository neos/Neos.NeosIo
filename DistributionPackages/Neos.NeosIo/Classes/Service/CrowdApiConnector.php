<?php
declare(strict_types=1);

namespace Neos\NeosIo\Service;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\InfiniteRedirectionException;
use Neos\Flow\Log\Utility\LogEnvironment;

#[Flow\Scope('singleton')]
class CrowdApiConnector extends AbstractApiConnector
{
    #[Flow\InjectConfiguration('crowdApi', 'Neos.NeosIo')]
    protected ?array $apiSettings;

    /**
     * Retrieves the list of groups from crowd
     * @return array{ name: string, description: string, members: array<string>, neos_group_type?: string, ... }[]|false An array of groups or false if no groups could be fetched
     * @throws \JsonException
     * @throws InfiniteRedirectionException
     */
    public function fetchGroups(bool $useCache = true): false|array
    {
        $cacheKey = $this->getCacheKey('groups');
        $groups = $useCache ? $this->getItem($cacheKey) : false;
        if ($groups === false) {
            $groups = [];
            $this->logger->info('Fetching groups from Crowd Api', LogEnvironment::fromMethodName(__METHOD__));
            $searchResult = $this->fetchJsonData('search', [
                'entity-type' => 'group',
                'expand' => 'group,attributes'
            ]);

            if (is_array($searchResult) && array_key_exists('groups', $searchResult)) {
                $groups = $this->storeGroups($searchResult['groups']);
            } else {
                $this->logger->error('Unknown error when fetching groups from Crowd Api, see system log', LogEnvironment::fromMethodName(__METHOD__));
            }
        }

        return $groups;
    }

    /**
     * Retrieves the list of active group members from the api
     * As the group member result already contains all the user data we store each user in the cache
     * to speed up further requests to fetch user data
     *
     * The api returns a result similar to this
     *
     * {
     *   "expand": "user",
     *   "users": [
     *     {
     *       "link": {
     *         "href": "https://crowd.neos.io/rest/usermanagement/latest/user?username=<username>",
     *         "rel": "self"
     *       },
     *       "name": "<username>",
     *       "password": {
     *         "link": {
     *           "href": "https://crowd.neos.io/rest/usermanagement/latest/user/password?username=<username>",
     *           "rel": "edit"
     *         }
     *       },
     *       "key": "<key>",
     *       "active": true,
     *       "attributes": {
     *         "attributes": [],
     *         "link": {
     *           "href": "https://crowd.neos.io/rest/usermanagement/latest/user/attribute?username=<username>",
     *           "rel": "self"
     *         }
     *       },
     *       "first-name": "<firstname>",
     *       "last-name": "<lastname>",
     *       "display-name": "<firstname> <lastname>",
     *       "email": "<email>"
     *     }
     *   ]
     * }
     *
     * @return array<array{link: array{href: string, rel: string}, name: string, password: array{link: array{href: string, rel: string}}, key: string, active: bool, attributes: array{attributes: array<array{name: string, values: array<string>}>, link: array{href: string, rel: string}}, 'first-name': string, 'last-name': string, 'display-name': string, email: string}>
     */
    protected function fetchGroupMembers(string $groupName): array
    {
        $result = $this->fetchJsonData('getGroupMembers', [
            'groupname' => $groupName,
            'expand' => 'user',
        ]);

        if (array_key_exists('users', $result)) {
            return array_reduce($result['users'], static function ($users, $userData) {
                if ($userData['active']) {
                    $users[] = $userData['name'];
                }
                return $users;
            }, []);
        }
        return [];
    }

    /**
     * Fetches the data of a single user
     *
     * The api returns a result similar to this
     *
     * {
     *   "expand": "attributes",
     *   "link": {
     *      "href": "https://crowd.neos.io/rest/usermanagement/latest/user?username=<username>",
     *      "rel": "self"
     *   },
     *   "name": "<username>",
     *   "password": {
     *      "link": {
     *          "href": "https://crowd.neos.io/rest/usermanagement/latest/user/password?username=<username>",
     *          "rel": "edit"
     *      }
     *   },
     *   "key": "<key>",
     *   "active": true,
     *   "attributes": {
     *      "attributes": [],
     *      "link": {
     *          "href": "https://crowd.neos.io/rest/usermanagement/latest/user/attribute?username=<username>",
     *          "rel": "self"
     *      }
     *   },
     *   "first-name": "<firstname>",
     *   "last-name": "<lastname>",
     *   "display-name": "<firstname> <lastname>",
     *   "email": "<email>"
     * }
     * @return false|array{name: string, display-name: string, email: string, first-name: string, last-name: string} An array with the user data or false if the user could not be fetched
     */
    public function fetchUser(string $userName, bool $useCache = true): false|array
    {
        $cacheKey = $this->getCacheKey('user__' . $userName);
        $user = $useCache ? $this->getItem($cacheKey) : false;

        if ($user === false) {
            $this->logger->info('Fetching user from Crowd Api', LogEnvironment::fromMethodName(__METHOD__));
            $userData = $this->fetchJsonData('getUser', [
                'username' => $userName,
                'expand' => 'attributes'
            ]);

            if (is_array($userData) && $userData['active']) {
                $user = $this->storeUser($userData);
            } else {
                $this->logger->error('Unknown error when fetching groups from Crowd Api, see system log',
                    LogEnvironment::fromMethodName(__METHOD__));
            }
        }

        return $user;
    }

    /**
     * @param array{name: string, display-name: string, email: string, first-name: string, last-name: string, attributes: array{ attributes: array{ values: array<int, mixed>, name: string }[] }} $userData
     * @return array{name: string, display-name: string, email: string, first-name: string, last-name: string} The processed user data
     */
    protected function storeUser(array $userData): array
    {
        $cacheKey = $this->getCacheKey('user__' . $userData['name']);

        $user = [
            'name' => $userData['name'],
            'display-name' => $userData['display-name'],
            'email' => $userData['email'],
            'first-name' => $userData['first-name'],
            'last-name' => $userData['last-name'],
        ];

        // Add custom attributes
        foreach ($userData['attributes']['attributes'] as $attribute) {
            if (isset($attribute['values'][0])) {
                $user[$attribute['name']] = $attribute['values'][0];
            }
        }

        $this->setItem($cacheKey, $user);
        return $user;
    }

    /**
     * @param array{ name: string, description?: string, attributes: array{ attributes: array<int, mixed> } }[] $groupsData
     * @return array{ name: string, description: string, members: array<string>, ... }[] An array of groups with their attributes and members
     */
    protected function storeGroups(array $groupsData): array
    {
        $groups = array_map(function ($group) {
            // Add crowd base attributes and members
            $groupData = [
                'name' => $group['name'],
                'description' => $group['description'] ?? '',
                'members' => $this->fetchGroupMembers($group['name']),
            ];

            // Add custom attributes
            foreach ($group['attributes']['attributes'] as $attribute) {
                if (isset($attribute['values'][0])) {
                    $groupData[$attribute['name']] = $attribute['values'][0];
                }
            }

            return $groupData;
        }, $groupsData);

        $this->setItem($this->getCacheKey('groups'), $groups);
        return $groups;
    }

    /**
     * Remotely sets the given groups attributes in crowd
     *
     * @param array<string, mixed> $attributes A key value list of attributes and their desired values
     */
    public function setGroupAttributes(string $groupName, array $attributes): bool
    {
        $attributesData = [
            'attributes' => []
        ];

        foreach ($attributes as $attribute => $value) {
            $attributesData['attributes'][] = [
                'name' => $attribute,
                'values' => [$value], // Crowd expects an array here
            ];
        }

        // Flush group cache after modifying one of them
        $this->unsetItem($this->getCacheKey('groups'));

        return $this->postJsonData('setGroupAttributes', ['groupname' => $groupName], $attributesData);
    }

    /**
     * Remotely sets the given users attributes in crowd
     * @param array<string, mixed> $attributes A key value list of attributes and their desired values
     */
    public function setUserAttributes(string $userName, array $attributes): bool
    {
        $attributesData = [
            'attributes' => []
        ];

        foreach ($attributes as $attribute => $value) {
            $attributesData['attributes'][] = [
                'name' => $attribute,
                'values' => [$value], // Crowd expects an array here
            ];
        }

        // Flush cached user after modifying one of them
        $this->unsetItem($this->getCacheKey('user__' . $userName));

        return $this->postJsonData('setUserAttributes', ['username' => $userName], $attributesData);
    }
}
