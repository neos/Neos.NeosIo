<?php
namespace Neos\NeosIo\TypoScript;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\FusionObjects\Helpers\FluidView;

/**
 * RtdProjectImplementation
 */
class RtdProjectImplementation extends \Neos\Fusion\FusionObjects\TemplateImplementation
{

    /**
     * @Flow\Inject
     * @var \Neos\Flow\Http\Client\Browser
     */
    protected $browser;

    /**
     * @return void
     */
    protected function initializeObject()
    {
        $this->browser->setRequestEngine(new \Neos\Flow\Http\Client\CurlEngine());
    }

    /**
     * @param FluidView $fluidView
     * @return void
     */
    protected function initializeView(FluidView $fluidView)
    {
        $decodedProject = $this->getProjectData($this['project']);

        $fluidView->assign('project', $decodedProject);
    }

    /**
     * @param string $projectSlug
     * @return array
     * @throws \Neos\Flow\Http\Client\InfiniteRedirectionException
     */
    protected function getProjectData($projectSlug)
    {
        $response = $this->browser->request(sprintf('http://readthedocs.org/api/v1/project/%s', $projectSlug));
        $projectData = json_decode($response, true);

        return $projectData;
    }
}
