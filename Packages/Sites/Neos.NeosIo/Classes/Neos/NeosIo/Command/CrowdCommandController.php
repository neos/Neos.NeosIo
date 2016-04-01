<?php

namespace Neos\NeosIo\Command;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use TYPO3\Flow\Annotations as Flow;
use Neos\NeosIo\Service\CrowdApiConnector;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\I18n\Translator;

/**
 * The Crowd Command Controller
 *
 * @Flow\Scope("singleton")
 */
class CrowdCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var CrowdApiConnector
     */
    protected $crowdApiConnector;

    /**
     * @Flow\InjectConfiguration(package="Neos.NeosIo", path="crowdApi")
     * @var array
     */
    protected $settings;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * Retrieves the list of groups from crowd and prints their attributes
     */
    public function listGroupsCommand()
    {
        $validAttributes = $this->settings['additionalAttributes']['group'];
        $headerRow = ['Name', 'Description'];

        foreach ($validAttributes as $attribute) {
            $headerRow[] = $this->translator->translateById('attribute.' . $attribute, [], null, null, 'CrowdApi',
                'Neos.NeosIo');
        }

        $groups = $this->crowdApiConnector->fetchGroups(false);
        $tableRows = array_map(function ($item) use ($validAttributes) {
            $attributes = [
                $item['name'],
                $item['description'],
            ];

            foreach ($validAttributes as $attribute) {
                $attributes[] = array_key_exists($attribute, $item) ? $item[$attribute] : '';
            }
            return $attributes;
        }, $groups);

        $this->output->outputTable($tableRows, $headerRow);
    }

    /**
     * Retrieves a user from crowd and prints their attributes
     * @param string $userName
     */
    public function showUserCommand($userName)
    {
        $validAttributes = $this->settings['additionalAttributes']['user'];
        $headerRow = ['Name', 'Fullname'];

        foreach ($validAttributes as $attribute) {
            $headerRow[] = $this->translator->translateById('attribute.' . $attribute, [], null, null, 'CrowdApi',
                'Neos.NeosIo');
        }

        $user = $this->crowdApiConnector->fetchUser($userName, false);
        $attributes = [
            $user['name'],
            $user['display-name'],
        ];

        foreach ($validAttributes as $attribute) {
            $attributes[] = array_key_exists($attribute, $user) ? $user[$attribute] : '';
        }

        $this->output->outputTable([$attributes], $headerRow);
    }

    /**
     * Sets the value of the given attribute in crowd.
     * Only predefined attributes are allowed.
     *
     * @param string $groupName
     * @param string $attribute
     * @param string $value
     */
    public function setGroupAttributeCommand($groupName, $attribute, $value)
    {
        $validAttributes = $this->settings['additionalAttributes']['group'];

        if (!in_array($attribute, $validAttributes)) {
            $this->outputFormatted('The attribute "%s" is not in the list of allowed attributes "%s"', [
                $attribute,
                join(', ', $validAttributes),
            ]);
        } else if (empty($value)) {
            $this->outputFormatted('The value for attribute "%s" cannot be empty', [$attribute]);
        } else if (empty($groupName)) {
            $this->outputLine('The groupname cannot be empty');
        } else {
            $result = $this->crowdApiConnector->setGroupAttributes($groupName, [$attribute => $value]);

            if ($result) {
                $this->outputFormatted('Set attribute "%s" for group "%s" to "%s"', [$attribute, $groupName, $value]);
            } else {
                $this->outputFormatted('Failed setting attribute "%s" for group "%s" to "%s". Check the log for errors.',
                    [$attribute, $groupName, $value]);
            }
        }
    }

    /**
     * Sets the value of the given attribute in crowd.
     * Only predefined attributes are allowed.
     *
     * @param string $userName
     * @param string $attribute
     * @param string $value
     */
    public function setUserAttributeCommand($userName, $attribute, $value)
    {
        $validAttributes = $this->settings['additionalAttributes']['user'];

        if (!in_array($attribute, $validAttributes)) {
            $this->outputFormatted('The attribute "%s" is not in the list of allowed attributes "%s"', [
                $attribute,
                join(', ', $validAttributes),
            ]);
        } else if (empty($value)) {
            $this->outputFormatted('The value for attribute "%s" cannot be empty', [$attribute]);
        } else if (empty($userName)) {
            $this->outputLine('The username cannot be empty');
        } else {
            $result = $this->crowdApiConnector->setUserAttributes($userName, [$attribute => $value]);

            if ($result) {
                $this->outputFormatted('Set attribute "%s" for user "%s" to "%s"', [$attribute, $userName, $value]);
            } else {
                $this->outputFormatted('Failed setting attribute "%s" for user "%s" to "%s". Check the log for errors.',
                    [$attribute, $userName, $value]);
            }
        }
    }
}
