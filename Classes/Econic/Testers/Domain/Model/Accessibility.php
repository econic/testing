<?php
namespace Econic\Testers\Domain\Model;

use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Helper trait for solving visibility related problems
 */
trait Accessibility {

	/**
	 * Invokes a method via php reflection regardless of its visibility
	 * 
	 * @param  string $methodName the method name
	 * @param  array  $arguments  the arguments that should be passed to the method
	 * @return mixed
	 */
	protected function invokeMethod($methodName, $arguments = null) {
		$reflectionObject = new \ReflectionObject($this->fixture);

		$reflectionMethod = $reflectionObject->getMethod($methodName);
		$reflectionMethod->setAccessible(true);

		if (empty($arguments)) {
			return $reflectionMethod->invoke( $this->fixture );
		} else {
			return $reflectionMethod->invokeArgs( $this->fixture, $arguments );
		}
	}

	/**
	 * Getter for properties, regardless of the visibility
	 * 
	 * @param  string $propertyName the name of the property
	 * @return mixed
	 */
	protected function getProperty($propertyName) {
		$reflectionObject = new \ReflectionObject($this->fixture);

		$reflectionProperty = $reflectionObject->getProperty($propertyName);
		$reflectionProperty->setAccessible(true);
		return $reflectionProperty->getValue( $this->fixture );
	}

	/**
	 * Setter for properties, regardless of the visibility
	 * 
	 * @param  string $propertyName  the name of the property
	 * @param  mixed  $propertyValue the value of the property
	 * @return void
	 */
	protected function setProperty($propertyName, $propertyValue) {
		ObjectAccess::setProperty($this->fixture, $propertyName, $propertyValue, true);
	}

}