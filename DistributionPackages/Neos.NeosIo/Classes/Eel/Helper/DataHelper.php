<?php
declare(strict_types=1);

namespace Neos\NeosIo\Eel\Helper;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Repository\UserRepository;

class DataHelper extends \Neos\Eel\Helper\ArrayHelper
{

    /**
     * @Flow\Inject
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @param string[] $userIdentifiers
     */
    public function users(array $userIdentifiers): string
    {
        $users = [];
        foreach ($userIdentifiers as $userIdentifier) {
            $user = $this->userRepository->findByIdentifier($userIdentifier);
            if ($user) {
                $users[] = $user;
            }
        }
        return implode(', ', array_map(static fn ($user) => $user->getLabel(), $users));
    }
}
