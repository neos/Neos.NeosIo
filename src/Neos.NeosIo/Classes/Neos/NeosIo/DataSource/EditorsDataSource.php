<?php
namespace Neos\NeosIo\DataSource;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Service\DataSource\AbstractDataSource;
use Neos\ContentRepository\Domain\Model\NodeInterface;

class EditorsDataSource extends AbstractDataSource
{

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
    public function getData(NodeInterface $node = null, array $arguments)
    {
        $options = [];
        foreach ($this->userService->getUsers() as $user) {
            $options[$this->persistenceManager->getIdentifierByObject($user)] = ['label' => $user->getLabel()];
        }
        return $options;
    }
}
