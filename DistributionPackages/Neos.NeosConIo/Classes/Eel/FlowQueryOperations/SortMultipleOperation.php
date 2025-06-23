<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\Exception as EelException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Utility\ObjectAccess;

/**
 * "sort" operation working on ContentRepository nodes.
 * Sorts nodes by specified node properties.
 */
class SortMultipleOperation extends AbstractOperation
{

    /**
     * {@inheritdoc}
     *
     * @var string
     */
    protected static $shortName = 'sortMultiple';

    /**
     * {@inheritdoc}
     *
     * We can only handle ContentRepository Nodes.
     * @param array<string|int, mixed> $context
     */
    public function canEvaluate($context): bool
    {
        return count($context) === 0 || $context[0] instanceof Node;
    }

    /**
     * {@inheritdoc}
     *
     * The First argument is the node property to sort by. Works with internal arguments (_xyz) as well.
     * The Second argument is the sort direction (ASC or DESC).
     * @param array{0?: string, 1?: string} $arguments
     * @throws FlowQueryException|EelException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        $nodes = $flowQuery->getContext();

        // Check sort property
        if (!empty($arguments[0])) {
            $sortProperty = $arguments[0];
        } else {
            throw new FlowQueryException('Please provide a node property to sort by.', 1467881104);
        }

        // Check the sort direction
        if (!empty($arguments[1]) && in_array(strtoupper($arguments[1]), ['ASC', 'DESC'])) {
            $sortOrder = strtoupper($arguments[1]);
        } else {
            throw new FlowQueryException('Please provide a valid sort direction (ASC or DESC)', 1467881105);
        }

        $sortedNodes = [];
        $nodesByIdentifier = [];

        // Determine the property value to sort by
        /** @var Node $node */
        foreach ($nodes as $node) {
            if ($sortProperty[0] === '_') {
                $propertyValue = ObjectAccess::getPropertyPath($node, substr($sortProperty, 1));
            } else {
                $propertyValue = $node->getProperty($sortProperty);
            }

            if ($propertyValue instanceof \DateTimeInterface) {
                $propertyValue = $propertyValue->getTimestamp();
            }

            $nodesByIdentifier[$node->aggregateId->value] = [
                'node' => $node,
                'previousCount' => (new FlowQuery([$node]))->prevAll()->count(),
                'propertyValue' => $propertyValue
            ];
        }


        usort($nodesByIdentifier, function ($a, $b) use ($sortOrder) {
            $retval = $a['propertyValue'] <=> $b['propertyValue'];
            if ($retval === 0) {
                $retval = $a['previousCount'] <=> $b['previousCount'];
            }
            if ($sortOrder === 'DESC') {
                $retval *= -1;
            }
            return $retval;
        });

        // Build the sorted context that is returned
        foreach ($nodesByIdentifier as $value) {
            $sortedNodes[] = $value['node'];
        }

        $flowQuery->setContext($sortedNodes);
    }
}
