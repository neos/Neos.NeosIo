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

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Eel\FlowQuery\FlowQuery;

/**
 * EEL sort() operation to sort Nodes
 */
class SortOperation extends AbstractOperation
{

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    static protected $shortName = 'sort';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    static protected $priority = 99;

    /**
     * {@inheritdoc}
     *
     * We can only handle CR Nodes.
     */
    public function canEvaluate($context): bool
    {
        return (isset($context[0]) && ($context[0] instanceof Node)) || count($context) === 0;
    }

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        if (empty($arguments[0])) {
            throw new FlowQueryException('sort() needs property name by which nodes should be sorted', 1332492263);
        }

        $nodes = $flowQuery->getContext();
        $sortByPropertyPath = $arguments[0];
        $sortOrder = 'DESC';
        if (!empty($arguments[1]) && in_array($arguments[1], array('ASC', 'DESC'))) {
            $sortOrder = $arguments[1];
        }

        $sortedNodes = array();
        $sortSequence = array();
        $nodesByIdentifier = array();
        /** @var Node $node */
        foreach ($nodes as $node) {
            $propertyValue = $node->getProperty($sortByPropertyPath);
            if ($propertyValue instanceof \DateTimeInterface) {
                $propertyValue = $propertyValue->getTimestamp();
            }
            $sortSequence[$node->aggregateId->value] = $propertyValue;
            $nodesByIdentifier[$node->aggregateId->value] = $node;
        }
        if ($sortOrder === 'DESC') {
            arsort($sortSequence);
        } else {
            asort($sortSequence);
        }
        foreach ($sortSequence as $nodeIdentifier => $value) {
            $sortedNodes[] = $nodesByIdentifier[$nodeIdentifier];
        }
        $flowQuery->setContext($sortedNodes);
    }
}
