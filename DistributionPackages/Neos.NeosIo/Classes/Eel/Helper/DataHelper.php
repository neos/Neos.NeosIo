<?php
declare(strict_types=1);

namespace Neos\NeosIo\Eel\Helper;

use Neos\Eel\Helper\ArrayHelper;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\UserRepository;

class DataHelper extends ArrayHelper
{

    #[Flow\Inject]
    protected UserRepository $userRepository;

    /**
     * @param string[] $userIdentifiers
     */
    public function users(array $userIdentifiers): string
    {
        /** @var User[] $users */
        $users = [];
        foreach ($userIdentifiers as $userIdentifier) {
            /** @var User|null $user */
            $user = $this->userRepository->findByIdentifier($userIdentifier);
            if ($user) {
                $users[] = $user;
            }
        }
        return implode(', ', array_map(static fn(User $user) => $user->getLabel(), $users));
    }
}
