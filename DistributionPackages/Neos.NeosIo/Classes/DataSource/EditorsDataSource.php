<?php
declare(strict_types=1);

namespace Neos\NeosIo\DataSource;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Service\DataSource\AbstractDataSource;

class EditorsDataSource extends AbstractDataSource
{

    /**
     * @var string
     */
    static protected $identifier = 'neos-neosio-editors';

    #[Flow\Inject]
    protected UserService $userService;

    /**
     * @var PersistenceManagerInterface
     */
    #[Flow\Inject]
    protected $persistenceManager;

    /**
     * @param array{} $arguments Additional arguments (key / value)
     * @return array<string, array{label: string}> An array of options for the editor data source
     */
    public function getData(?Node $node = null, array $arguments = []): array
    {
        $options = [];
        foreach ($this->userService->getUsers() as $user) {
            $options[(string)$this->persistenceManager->getIdentifierByObject($user)] = ['label' => (string)$user->getLabel()];
        }
        return $options;
    }
}
