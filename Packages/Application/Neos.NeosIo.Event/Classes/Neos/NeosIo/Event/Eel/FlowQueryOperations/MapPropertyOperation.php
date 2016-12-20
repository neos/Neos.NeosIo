<?php
namespace Neos\NeosIo\Event\Eel\FlowQueryOperations;

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Eel\FlowQuery\Operations\AbstractOperation;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * Retrieve a property of all results in the context and map it to an array
 */
class MapPropertyOperation extends AbstractOperation {

	/**
	 * {@inheritdoc}
	 *
	 * @var string
	 */
	static protected $shortName = 'mapProperty';

	/**
	 * {@inheritdoc}
	 *
	 * @var boolean
	 */
	static protected $final = TRUE;

	/**
	 * {@inheritdoc}
	 *
	 * We can only handle TYPO3CR Nodes (but also an empty context)
	 *
	 * @param mixed $context
	 * @return boolean
	 */
	public function canEvaluate($context) {
		if (empty($context)) {
			return TRUE;
		}
		$firstElement = reset($context);
		return $firstElement === NULL || $firstElement instanceof NodeInterface;
	}

	/**
	 * {@inheritdoc}
	 *
	 * @param FlowQuery $flowQuery the FlowQuery object
	 * @param array $arguments the arguments for this operation
	 * @return mixed
	 */
	public function evaluate(FlowQuery $flowQuery, array $arguments) {
		if (!isset($arguments[0]) || empty($arguments[0])) {
			throw new \TYPO3\Eel\FlowQuery\FlowQueryException('mapProperty() does not support returning all attributes', 1429712387);
		} else {
			$context = $flowQuery->getContext();
			$propertyPath = $arguments[0];

			$result = array();
			foreach ($context as $element) {
				if ($element instanceof NodeInterface) {
					if ($propertyPath[0] === '_') {
						$result[] = \TYPO3\Flow\Reflection\ObjectAccess::getPropertyPath($element, substr($propertyPath, 1));
					} else {
						$result[] = $element->getProperty($propertyPath);
					}
				}
			}
			return $result;
		}
	}
}
