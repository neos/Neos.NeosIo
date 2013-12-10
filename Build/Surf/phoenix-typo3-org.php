<?php
use \TYPO3\Surf\Domain\Model\Workflow;
use \TYPO3\Surf\Domain\Model\Node;
use \TYPO3\Surf\Domain\Model\SimpleWorkflow;

/**
 * For this deployment the following env variables are required:
 *
 * DEPLOYMENT_PATH: path on the remote server to deploy to
 * DEPLOYMENT_USER: username to connect to the remote server
 * DEPLOYMENT_HOST: node host name
 */

$application = new \TYPO3\Surf\Application\TYPO3();
if (getenv('DEPLOYMENT_PATH')) {
	$application->setDeploymentPath(getenv('DEPLOYMENT_PATH'));
} else {
	throw new \Exception('Deployment path must be set in the DEPLOYMENT_PATH env variable.');
}

$application->setOption('repositoryUrl', 'git://git.typo3.org/Sites/NeosTypo3Org.git');
$application->setOption('sitePackageKey', 'TYPO3.NeosTypo3Org');
$application->setOption('keepReleases', 50);
$application->setOption('composerCommandPath', 'php /var/www/vhosts/neos.typo3.org/home/composer.phar');


$deployment->addApplication($application);

$workflow = new SimpleWorkflow();
# $workflow->setEnableRollback(FALSE);
$deployment->setWorkflow($workflow);

$deployment->onInitialize(function() use ($workflow, $application) {
	$workflow->removeTask('typo3.surf:flow3:setfilepermissions');
	$workflow->removeTask('typo3.surf:flow3:copyconfiguration');
	$workflow->removeTask('typo3.surf:typo3:importsite');
});

#$workflow->afterTask('typo3.surf:symlinkrelease', array('typo3.surf:varnishpurge'), $application);

if (getenv('DEPLOYMENT_HOST')) {
	$hostName = getenv('DEPLOYMENT_HOST');
} else {
	throw new \Exception('Deployment host name must be set in the DEPLOYMENT_HOST env variable.');
}

$node = new Node($hostName);
$node->setHostname($hostName);
if (getenv('DEPLOYMENT_USERNAME')) {
	$node->setOption('username', getenv('DEPLOYMENT_USERNAME'));
} else {
	throw new \Exception('Username must be set in the DEPLOYMENT_USERNAME env variable.');
}

$application->addNode($node);
?>
