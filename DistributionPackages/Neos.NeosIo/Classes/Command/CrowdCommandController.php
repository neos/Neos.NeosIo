<?php
declare(strict_types=1);

namespace Neos\NeosIo\Command;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\I18n\Translator;
use Neos\NeosIo\Service\CrowdApiConnector;

/**
 * The Crowd Command Controller
 *
 * @Flow\Scope("singleton")
 */
class CrowdCommandController extends CommandController
{

    #[Flow\Inject]
    protected CrowdApiConnector $crowdApiConnector;

    /**
     * @var array{ additionalAttributes: array{ group: string[], user: string[] } }
     */
    #[Flow\InjectConfiguration('Neos.NeosIo', 'crowdApi')]
    protected array $settings;

    #[Flow\Inject]
    protected Translator $translator;

    /**
     * Retrieves the list of groups from crowd and prints their attributes
     */
    public function listGroupsCommand(): void
    {
        $validAttributes = $this->settings['additionalAttributes']['group'];
        $headerRow = ['Name', 'Description'];

        foreach ($validAttributes as $attribute) {
            $headerRow[] = $this->translator->translateById('attribute.' . $attribute, [], null, null, 'CrowdApi',
                'Neos.NeosIo');
        }

        $groups = $this->crowdApiConnector->fetchGroups(false);
        if ($groups === false) {
            $this->outputLine('No groups found or an error occurred while fetching groups.');
            return;
        }
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
     */
    public function showUserCommand(?string $userName = null): void
    {
        $validAttributes = $this->settings['additionalAttributes']['user'];
        $headerRow = ['Name', 'Fullname'];

        foreach ($validAttributes as $attribute) {
            $headerRow[] = $this->translator->translateById('attribute.' . $attribute, [], null, null, 'CrowdApi',
                'Neos.NeosIo');
        }

        $user = $this->crowdApiConnector->fetchUser($userName, false);
        if ($user === false) {
            $this->outputLine('No user found or an error occurred while fetching the user.');
            return;
        }
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
     */
    public function setGroupAttributeCommand(string $groupName, string $attribute, string $value): void
    {
        $validAttributes = $this->settings['additionalAttributes']['group'];

        if (!in_array($attribute, $validAttributes, true)) {
            $this->outputFormatted('The attribute "%s" is not in the list of allowed attributes "%s"', [
                $attribute,
                implode(', ', $validAttributes),
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
     */
    public function setUserAttributeCommand(string $userName, string $attribute, string $value): void
    {
        $validAttributes = $this->settings['additionalAttributes']['user'];

        if (!in_array($attribute, $validAttributes, true)) {
            $this->outputFormatted('The attribute "%s" is not in the list of allowed attributes "%s"', [
                $attribute,
                implode(', ', $validAttributes),
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

    /**
     * Import userdata from a csv file and update crowd
     *
     * Structure has to similiar to this:
     *
     * username;neos_email;neos_twitter;...
     * xyz;xyz;xyz@example.org;@xyz;...
     * ...
     *
     * "username" is the only required field and has to contain the usernames from crowd.
     * The other columns are optional and only the ones which match the "additionalAttributes" in the
     * Settings.yaml are read.
     *
     * @param string $csvPath the path to a csv file with userdata
     */
    public function importUserAttributesCommand(string $csvPath, string $delimiter = ';'): void
    {
        $validAttributes = $this->settings['additionalAttributes']['user'];
        $validAttributes[] = 'username';
        $columns = [];
        $fieldCount = 0;

        if (($handle = fopen($csvPath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
                // The first line is the column headers
                if (empty($columns)) {
                    $columns = $data;
                    $fieldCount = count($columns);
                    echo "Columns: " . join(', ', $columns) . "\n";
                }

                $attributes = [];
                for ($i = 0; $i < $fieldCount; $i++) {
                    $columnName = $columns[$i];
                    if (in_array($columnName, $validAttributes, true)) {
                        $attributes[$columnName] = $data[$i];
                    }
                }

                echo "Updating data: " . implode(', ', $data) . "\n";
                if (!array_key_exists('username', $attributes) || !is_string($attributes['username']) || trim($attributes['username']) === '') {
                    $this->outputLine('<error>Username is missing in the data row: ' . implode(', ', $data) . '</error>');
                    continue;
                }
                $this->crowdApiConnector->setUserAttributes($attributes['username'], $attributes);
            }
            fclose($handle);
        }
    }
}
