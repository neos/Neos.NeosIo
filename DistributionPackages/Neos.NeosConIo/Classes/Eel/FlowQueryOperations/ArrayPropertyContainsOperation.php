<?php
namespace Neos\NeosConIo\Eel\FlowQueryOperations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\ObjectAccess;
use Neos\ContentRepository\Domain\Model\Node;
use Neos\ContentRepository\Domain\Model\NodeInterface;

/**
 * This filter implementation contains specific behavior for use on ContentRepository
 * nodes. It will not evaluate any elements that are not instances of the
 * `NodeInterface`.
 *
 * The implementation changes the behavior of the `instanceof` operator to
 * work on node types instead of PHP object types, so that::
 *
 * 	[instanceof Neos.NodeTypes:Page]
 *
 * will in fact use `isOfType()` on the `NodeType` of context elements to
 * filter. This filter allow also to filter the current context by a given
 * node. Anything else remains unchanged.
 */
class ArrayPropertyContainsOperation extends AbstractOperation
{
    protected static $shortName = 'arrayPropertyContains';

    /**
     * {@inheritdoc}
     *
     * @var integer
     */
    protected static $priority = 100;

    /**
     * {@inheritdoc}
     *
     * @param FlowQuery $flowQuery
     * @param array $arguments
     * @return void
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments)
    {

        $propertyNameToFilter = $arguments[0];
        $valueToCheck = $arguments[1];

        $filteredContext = array();
        $context = $flowQuery->getContext();
        foreach ($context as $element) {
            /* @var $element NodeInterface */
            $propertyValue = $element->getProperty($propertyNameToFilter);
            if (in_array($valueToCheck, $propertyValue)) {
                $filteredContext[] = $element;
            }
        }
        $flowQuery->setContext($filteredContext);
    }

}
