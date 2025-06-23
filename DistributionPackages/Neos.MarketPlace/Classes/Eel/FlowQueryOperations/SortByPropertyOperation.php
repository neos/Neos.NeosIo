<?php
declare(strict_types=1);

namespace Neos\MarketPlace\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.MarketPlace package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * Flowquery operation to sort Nodes
 */
class SortByPropertyOperation extends AbstractOperation
{

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    static protected $shortName = 'sortByProperty';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    static protected $priority = 99;

    /**
     * {@inheritdoc}
     * @param array{0?: string, 1?: string} $arguments
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        if (empty($arguments[0])) {
            throw new FlowQueryException('sort() needs property name by which entries should be sorted', 1581928004);
        }

        /** @var array<string, mixed>[] $items */
        $items = $flowQuery->getContext();
        $propertyName = $arguments[0];

        uasort($items, static function (array $a, array $b) use ($propertyName) {
            if ($a[$propertyName] === $b[$propertyName]) {
                return 0;
            }
            return ($a[$propertyName] < $b[$propertyName]) ? -1 : 1;
        });

        if (!empty($arguments[1]) && $arguments[1] === 'DESC') {
            $items = array_reverse($items);
        }

        $flowQuery->setContext($items);
    }
}
