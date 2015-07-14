<?php
namespace Neos\NeosIo\TypoScript;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView;

/**
 * RtdProjectImplementation
 */
class RtdProjectImplementation extends \TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Http\Client\Browser
	 */
	protected $browser;

	/**
	 * @return void
	 */
	protected function initializeObject() {
		$this->browser->setRequestEngine(new \TYPO3\Flow\Http\Client\CurlEngine());
	}

	/**
	 * @param FluidView $fluidView
	 * @return void
	 */
	protected function initializeView(FluidView $fluidView) {
		$decodedProject = $this->getProjectData($this['project']);

		$fluidView->assign('project', $decodedProject);
	}

	/**
	 * @param string $projectSlug
	 * @return array
	 * @throws \TYPO3\Flow\Http\Client\InfiniteRedirectionException
	 */
	protected function getProjectData($projectSlug) {
		$response = $this->browser->request(sprintf('http://readthedocs.org/api/v1/project/%s', $projectSlug));
		$projectData = json_decode($response, TRUE);

		return $projectData;
	}

}