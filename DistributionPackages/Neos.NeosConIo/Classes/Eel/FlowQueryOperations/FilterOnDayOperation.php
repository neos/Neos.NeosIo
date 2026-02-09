<?php
declare(strict_types=1);

namespace Neos\NeosConIo\Eel\FlowQueryOperations;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Eel\FlowQuery\FlowQueryException;
use Neos\Eel\FlowQuery\Operations\AbstractOperation;

class FilterOnDayOperation extends AbstractOperation
{
    /**
     * {@inheritdoc}
     */
    protected static $shortName = 'filterOnDay';

    /**
     * {@inheritdoc}
     */
    protected static $priority = 100;

    /**
     * {@inheritdoc}
     *
     * We can only handle CR Nodes.
     * @param array<string|int, mixed> $context
     */
    public function canEvaluate($context): bool
    {
        return (!isset($context[0]) || ($context[0] instanceof Node));
    }

    /**
     * {@inheritdoc}
     *
     * @param array{0?: string|null, 1?: \DateTimeInterface} $arguments The arguments for this operation.
     *                         First argument is property to filter by, must be of reference of references type.
     *                         Second is object to filter by, must be Node.
     *
     * @throws FlowQueryException
     */
    public function evaluate(FlowQuery $flowQuery, array $arguments): void
    {
        if (empty($arguments[0])) {
            throw new FlowQueryException('filterOnDay() needs reference property name by which nodes should be filtered', 1332492263);
        }
        if (empty($arguments[1])) {
            throw new FlowQueryException('filterOnDay() needs day by which nodes should be filtered', 1332493263);
        }

        [$filterByPropertyPath, $day] = $arguments;

        $filteredNodes = [];
        foreach ($flowQuery->getContext() as $node) {
            /** @var Node $node */
            $propertyValue = $node->getProperty($filterByPropertyPath);
            if ($propertyValue instanceof \DateTimeInterface && $propertyValue->format('Y-m-d') === $day->format('Y-m-d')) {
                $filteredNodes[] = $node;
            }
        }

        $flowQuery->setContext($filteredNodes);
    }
}
