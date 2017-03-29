<?php
namespace Neos\NeosIo\Eel\Helper;

use Neos\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Repository\UserRepository;

class DataHelper extends \TYPO3\Eel\Helper\ArrayHelper
{

    /**
     * @Flow\Inject
     * @var UserRepository
     */
    protected $userRepository;

    /**
     * @param array $userIdentifiers
     * @return array<User>
     */
    public function users($userIdentifiers)
    {
        $users = [];
        foreach ($userIdentifiers as $userIdentifier) {
            $user = $this->userRepository->findByIdentifier($userIdentifier);
            if ($user) {
                $users[] = $user;
            }
        }
        return implode(', ', array_map(function ($user) {
            return $user->getLabel();
        }, $users));
    }
}
