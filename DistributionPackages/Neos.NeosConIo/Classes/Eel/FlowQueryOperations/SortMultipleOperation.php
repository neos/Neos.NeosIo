<?php
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
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

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
     *
     * @param mixed $context
     * @return boolean
     */
    public function canEvaluate($context)
    {
        return count($context) === 0 || (isset($context[0]) && ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * First argument is the node property to sort by. Works with internal arguments (_xyz) as well.
     * Second argument is the sort direction (ASC or DESC).
     *
     * @param FlowQuery $flowQuery the FlowQuery object
     * @param array $arguments the arguments for this operation.
     * @return mixed
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {
        $nodes = $flowQuery->getContext();

        // Check sort property
        if (isset($arguments[0]) && !empty($arguments[0])) {
            $sortProperty = $arguments[0];
        } else {
            throw new \Neos\Eel\FlowQuery\FlowQueryException('Please provide a node property to sort by.', 1467881104);
        }

        // Check sort direction
        if (isset($arguments[1]) && !empty($arguments[1]) && in_array(strtoupper($arguments[1]), ['ASC', 'DESC'])) {
            $sortOrder = strtoupper($arguments[1]);
        } else {
            throw new \Neos\Eel\FlowQuery\FlowQueryException('Please provide a valid sort direction (ASC or DESC)', 1467881105);
        }

        $sortedNodes = [];
        $nodesByIdentifier = [];

        // Determine the property value to sort by
        /** @var Node $node */
        foreach ($nodes as $node) {
            if ($sortProperty[0] === '_') {
                $propertyValue = \Neos\Utility\ObjectAccess::getPropertyPath($node, substr($sortProperty, 1));
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


        usort($nodesByIdentifier, function($a, $b) use($sortOrder) {
            $retval = $a['propertyValue'] <=> $b['propertyValue'];
            if ($retval == 0) {
                $retval = $a['previousCount'] <=> $b['previousCount'];
            }
            if ($sortOrder === 'DESC') {
                $retval *= -1;
            }
            return $retval;
        });

        // Build the sorted context that is returned
        foreach ($nodesByIdentifier as $nodeIdentifier => $value) {
            $sortedNodes[] = $value['node'];
        }

        $flowQuery->setContext($sortedNodes);
    }
}
