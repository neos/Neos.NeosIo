<?php
namespace Neos\NeosIo\DataSource;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\Neos\Service\DataSource\AbstractDataSource;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class EditorsDataSource extends AbstractDataSource {

	/**
	 * @var string
	 */
	static protected $identifier = 'neos-neosio-editors';

	/**
	 * @Flow\Inject
	 * @var UserService
	 */
	protected $userService;

	/**
	 * @Flow\Inject
	 * @var PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @param NodeInterface $node The node that is currently edited (optional)
	 * @param array $arguments Additional arguments (key / value)
	 * @return array
	 */
	public function getData(NodeInterface $node, array $arguments) {
		$options = [];
		foreach ($this->userService->getUsers() as $user) {
			$options[$this->persistenceManager->getIdentifierByObject($user)] = ['label' => $user->getLabel()];
		}
		return $options;
	}

}