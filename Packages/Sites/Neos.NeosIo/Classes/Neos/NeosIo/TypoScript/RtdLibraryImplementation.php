<?php
namespace Neos\NeosIo\TypoScript;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Arrays;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView;

/**
 * RtdLibraryImplementation
 */
class RtdLibraryImplementation extends \TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation
{

    /**
     * @Flow\Inject
     * @var \TYPO3\Flow\Http\Client\Browser
     */
    protected $browser;

    /**
     * @return void
     */
    protected function initializeObject()
    {
        $this->browser->setRequestEngine(new \TYPO3\Flow\Http\Client\CurlEngine());
    }

    /**
     * @return void
     */
    protected function initializeView(FluidView $fluidView)
    {
        $user = $this->getUserData($this['username']);
        $excludedProjects = Arrays::trimExplode(',', $this['excludedprojects']);
        $decodedProjects = $this->getProjectsData($user['id']);

        $projects = [];
        foreach ($decodedProjects['objects'] as $project) {
            if (!in_array($project['slug'], $excludedProjects)) {
                $projects[] = $project;
            }
        }

        $fluidView->assign('projects', $projects);
    }

    /**
     * @param string $username
     * @return array
     * @throws \TYPO3\Flow\Http\Client\InfiniteRedirectionException
     */
    protected function getUserData($username)
    {
        $response = $this->browser->request(sprintf('http://readthedocs.org/api/v1/user/%s/?format=json', $username));
        $userData = json_decode($response, true);

        return $userData;
    }

    /**
     * @param integer $userId
     * @param integer $limit
     * @param integer $offset
     * @return array
     * @throws \TYPO3\Flow\Http\Client\InfiniteRedirectionException
     */
    protected function getProjectsData($userId = null, $limit = null, $offset = null)
    {
        $query = http_build_query([
            'users' => $userId,
            'limit' => $limit,
            'offset' => $offset,
            'format' => 'json'
        ]);
        $response = $this->browser->request(sprintf('http://readthedocs.org/api/v1/project/?%s', $query));
        $projectsData = json_decode($response, true);

        return $projectsData;
    }
}
