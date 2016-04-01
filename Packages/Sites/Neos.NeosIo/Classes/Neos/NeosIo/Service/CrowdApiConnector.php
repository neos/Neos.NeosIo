<?php

namespace Neos\NeosIo\Service;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class CrowdApiConnector extends AbstractApiConnector
{
    /**
     * @Flow\InjectConfiguration(path="crowdApi", package="Neos.NeosIo")
     *
     * @var array
     */
    protected $apiSettings;

    /**
     * Retrieves the list of groups from crowd
     *
     * @param bool $useCache
     * @return array
     */
    public function fetchGroups($useCache = true)
    {
        $cacheKey = $this->getCacheKey('groups');
        $groups = $useCache ? $this->getItem($cacheKey) : false;
        if ($groups === false) {
            $groups = [];
            $this->systemLogger->log(sprintf('Fetching groups from Crowd Api'), LOG_INFO, 1456818441);
            $searchResult = $this->fetchJsonData('search', [
                'entity-type' => 'group',
                'expand' => 'group,attributes'
            ]);

            if (is_array($searchResult) && array_key_exists('groups', $searchResult)) {
                $groups = array_map(function ($group) {
                    $groupData = [
                        'name' => $group['name'],
                        'description' => array_key_exists('description', $group) ? $group['description'] : '',
                        'members' => $this->fetchGroupMembers($group['name']),
                    ];

                    foreach ($group['attributes']['attributes'] as $attribute) {
                        if (isset($attribute['values'][0])) {
                            $groupData[$attribute['name']] = $attribute['values'][0];
                        }
                    }

                    return $groupData;
                }, $searchResult['groups']);
                $this->setItem($cacheKey, $groups);
            } else {
                $this->systemLogger->log(sprintf('Unknown error when fetching groups from Crowd Api, see system log'), LOG_ERR, 1456973717);
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
     * @param string $groupName
     * @return array
     */
    protected function fetchGroupMembers($groupName)
    {
        $result = $this->fetchJsonData('getGroupMembers', [
            'groupname' => $groupName,
            'expand' => 'user',
        ]);

        if (array_key_exists('users', $result)) {
            $groupMembers = array_reduce($result['users'], function ($users, $userData) {
                if ($userData['active']) {
                    $users[]= $this->storeUser($userData);
                }
                return $users;
            }, []);
            return $groupMembers;
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
     *
     * @param string $userName
     * @return array|bool The users data or False if no user could be found
     */
    public function fetchUser($userName)
    {
        $cacheKey = $this->getCacheKey('user__' . $userName);
        $result = $this->getItem($cacheKey);

        if ($result === false) {
            $this->systemLogger->log('Fetching user from Crowd Api', LOG_INFO, 1456818440);
            $userData = $this->fetchJsonData('getUser', [
                'username' => $userName,
            ]);

            if (is_array($userData) && $userData['active']) {
                $this->storeUser($userData);
            } else {
                $this->systemLogger->log('Unknown error when fetching groups from Crowd Api, see system log',
                    LOG_ERR, 1456973717);
            }
        }

        return $result;
    }

    /**
     * @param array $userData
     * @return array The processed user data
     */
    protected function storeUser(array $userData)
    {
        $cacheKey = $this->getCacheKey('user__' . $userData['name']);
        $user = [
            'name' => $userData['name'],
            'firstName' => $userData['first-name'],
            'lastName' => $userData['last-name'],
            'email' => $userData['email'],
            'displayName' => $userData['display-name'],
        ];
        $this->setItem($cacheKey, $user);
        return $user;
    }

    /**
     * Remotely sets the given attributes in crowd
     *
     * @param string $groupName
     * @param array $attributes A key value list of attributes and their desired values
     * @return bool
     */
    public function setGroupAttributes($groupName, array $attributes)
    {
        $attributesData = [
            'attributes' => []
        ];

        foreach ($attributes as $attribute => $value) {
            $attributesData['attributes'][]= [
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
     *
     * @param string $userName
     * @param array $attributes A key value list of attributes and their desired values
     * @return bool
     */
    public function setUserAttributes($userName, array $attributes)
    {
        $attributesData = [
            'attributes' => []
        ];

        foreach ($attributes as $attribute => $value) {
            $attributesData['attributes'][]= [
                'name' => $attribute,
                'values' => [$value], // Crowd expects an array here
            ];
        }

        // Flush cached user after modifying one of them
        $this->unsetItem($this->getCacheKey('user__' . $userName));

        return $this->postJsonData('setUserAttributes', ['username' => $userName], $attributesData);
    }
}
