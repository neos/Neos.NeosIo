<?php

namespace Neos\NeosIo\TypoScript\FlowQueryOperations;

/*
 * The Neos project is licensed under GPL v3 or later
 */

use Neos\NeosIo\Service\FundingApiConnector;
use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;

class TeamListingOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'teamListing';

    /**
     * @Flow\Inject
     *
     * @var FundingApiConnector
     */
    protected $apiConnector;

    /**
     * {@inheritdoc}
     *
     * @param array (or array-like object) $context onto which this operation should be applied
     *
     * @return bool TRUE if the operation can be applied onto the $context, FALSE otherwise
     */
    public function canEvaluate($context)
    {
        return true;
    }

    /**
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $result = array(
            array(
                "first-name" => "Markus",
                "last-name" => "Goldbeck",
                "display-name" => "Markus Goldbeck",
                "email" => "markus@neos.io"
            ),
            array(
                "first-name" => "Markus",
                "last-name" => "Goldbeck",
                "display-name" => "Markus Goldbeck",
                "email" => "markus@neos.io"
            ),
            array(
                "first-name" => "Markus",
                "last-name" => "Goldbeck",
                "display-name" => "Markus Goldbeck",
                "email" => "markus@neos.io"
            ),
            array(
                "first-name" => "Markus",
                "last-name" => "Goldbeck",
                "display-name" => "Markus Goldbeck",
                "email" => "markus@neos.io"
            ),
            array(
                "first-name" => "Markus",
                "last-name" => "Goldbeck",
                "display-name" => "Markus Goldbeck",
                "email" => "markus@neos.io"
            ),
            array(
                "first-name" => "Markus",
                "last-name" => "Goldbeck",
                "display-name" => "Markus Goldbeck",
                "email" => "markus@neos.io"
            ),
            array(
                "first-name" => "Markus",
                "last-name" => "Goldbeck",
                "display-name" => "Markus Goldbeck",
                "email" => "markus@neos.io"
            ),
            
            array(
                "first-name" => "john",
                "last-name" => "doe",
                "display-name" => "John Doe",
                "email" => "test@gmail.com"
            )
        );

        $flowQuery->setContext($result);
    }
}
