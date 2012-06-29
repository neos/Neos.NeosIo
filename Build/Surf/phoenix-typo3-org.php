<?php
use \TYPO3\Surf\Domain\Model\Workflow;
use \TYPO3\Surf\Domain\Model\Node;
use \TYPO3\Surf\Domain\Model\SimpleWorkflow;

/**
 * For this deployment the following env variables are required:
 *
 * DEPLOYMENT_PATH: path on the remote server to deploy to
 * DEPLOYMENT_USER: username to connect to the remote server
 */

$application = new \TYPO3\Surf\Application\TYPO3();
if (getenv('DEPLOYMENT_PATH')) {
	$application->setDeploymentPath(getenv('DEPLOYMENT_PATH'));
} else {
	throw new \Exception('Deployment path must be set in the DEPLOYMENT_PATH env variable.');
}

$application->setOption('repositoryUrl', 'git://git.typo3.org/Sites/PhoenixTypo3Org.git');
$application->setOption('sitePackageKey', 'TYPO3.PhoenixTypo3Org');
$application->setOption('keepReleases', 20);

$deployment->addApplication($application);

$workflow = new SimpleWorkflow();
# $workflow->setEnableRollback(FALSE);
$deployment->setWorkflow($workflow);

$deployment->onInitialize(function() use ($workflow, $application) {
	$workflow->removeTask('typo3.surf:flow3:setfilepermissions');
	$workflow->removeTask('typo3.surf:flow3:copyconfiguration');
});

$workflow->defineTask('x:renderdocumentation', 'typo3.surf:flow3:runcommand', array('command' => 'documentation:render --bundle PhoenixDocumentation'));
$workflow->defineTask('x:importgettingstarteddocumentation', 'typo3.surf:flow3:runcommand', array('command' => 'documentation:import --bundle PhoenixGettingStarted'));
$workflow->defineTask('x:importfeaturedocumentation', 'typo3.surf:flow3:runcommand', array('command' => 'documentation:import --bundle PhoenixFeatures'));
$workflow->afterTask('typo3.surf:typo3:importsite', array('x:renderdocumentation', 'x:importgettingstarteddocumentation', 'x:importfeaturedocumentation'));

#$workflow->afterTask('typo3.surf:symlinkrelease', array('typo3.surf:varnishpurge'), $application);

$node = new Node('phoenix.typo3.org');
$node->setHostname('phoenix.typo3.org');
if (getenv('DEPLOYMENT_USERNAME')) {
	$node->setOption('username', getenv('DEPLOYMENT_USERNAME'));
} else {
	throw new \Exception('Username must be set in the DEPLOYMENT_USERNAME env variable.');
}

$application->addNode($node);
?>