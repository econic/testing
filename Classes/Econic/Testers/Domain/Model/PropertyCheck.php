<?php
namespace Econic\Testers\Domain\Model;

use TYPO3\Flow\Annotations as Flow;
use Doctrine\Common\Collections\ArrayCollection;
use Econic\Testers\Utility\PluralizerUtility;

/**
 * Tester class for testing Model/Entity related things
 */
trait PropertyCheck {

	/**
	 * checks the if the given property has a correct setup
	 * checks for get/set(+chaining)
	 * 
	 * @param  string $propertyName the property name
	 * @param  string $propertyType the property type: integer|boolean|float|string|array
	 * @param  mixed $testValue     the value to test the setter with
	 * @param  mixed $defaultValue  the value that's expected as default value
	 * 
	 * @return void
	 */
	protected function checkPropertyWithSimpleType($propertyName, $propertyType, $testValue = null, $defaultValue = null) {

		if ( $testValue === null ) {

			$defaultValues = array(
				'boolean' => true,
				'integer' => 1,
				'float' => 0.4,
				'string' => 'abc',
				'array' => array( 'foo', 'bar', 'baz')
			);

			if (array_key_exists($propertyType, $defaultValues)) {
				$testValue = $defaultValues[$propertyType];
			} else {
				throw new \Exception('Unknown simple type: ' . $propertyType);
			}

		}
		
		$this->checkSimplePropertyWithValue( $propertyName, $testValue, $defaultValue );

	}

	/**
	 * checks the if the given property has a correct setup
	 * checks for get/set(+chaining)
	 * 
	 * @param  string $propertyName name of the property
	 * @param  string $propertyType type of the property
	 * @param  mixed $testValue     the value to test the setter with
	 * @param  mixed $defaultValue  the value that's expected as default value
	 * 
	 * @return void
	 */
	protected function checkPropertyWithObjectType($propertyName, $propertyType, $testValue = null, $defaultValue = null) {
		if ( $testValue === null ) {
			$testValue = new $propertyType();
		}
		$this->checkSimplePropertyWithValue( $propertyName, $testValue, $defaultValue );
	}

	/**
	 * checks the if the given property has a correct setup
	 * checks for get/set(+chaining)
	 * 
	 * @param  string $propertyName the property name
	 * @param  mixed $value         the dummy value to test with
	 * @param  mixed $defaultValue  the value that's expected as default value
	 * @return void
	 */
	protected function checkSimplePropertyWithValue($propertyName, $value, $defaultValue) {

		// assert that methods exist
		$this->assertPropertyFunctionsExist($propertyName, array('get','set'));

		$propertyGetterName = $this->getMethodNameForProperty($propertyName, 'get');
		$propertySetterName = $this->getMethodNameForProperty($propertyName, 'set');

		// check default value with getter
		$this->assertSame(
			$defaultValue,
			$this->fixture->{$propertyGetterName}(),
			'Class <' . get_class($this->fixture) . '> does not have the correct default value for property <' . $propertyName . '>, tried ' . $propertyGetterName . '()'
		);

		// check chaining while using setter
		$this->assertSame(
			$this->fixture,
			$this->fixture->{$propertySetterName}($value),
			'Class <' . get_class($this->fixture) . '> does not enable chaining for property <' . $propertyName . '>, tried ' . $propertySetterName . '()'
		);

		// check set/get
		$this->assertSame(
			$value,
			$this->fixture->{$propertyGetterName}()
		);
	}

	/**
	 * checks the if the given collection property has a correct setup
	 * checks for default/get/set(+chaining)/add(+chaining)/remove(+chaining)
	 * 
	 * @param  string $propertyName       name of the property
	 * @param  string $collectionItemType type of the items inside the collection
	 * @return void
	 */
	protected function checkPropertyWithObjectCollectionType($propertyName, $collectionItemType, $functionsToTest = array('get', 'set', 'add', 'remove')) {

		// assert that methods exist
		$this->assertPropertyFunctionsExist($propertyName, $functionsToTest);

		$item1 = new $collectionItemType();
		$item2 = new $collectionItemType();
		$item3 = new $collectionItemType();
		$itemCollection = new ArrayCollection();
		$itemCollection->add($item1);
		$itemCollection->add($item2);
		$itemCollection->add($item3);

		$singularPropertyName = PluralizerUtility::singular($propertyName);
		$propertyGetterName = $this->getMethodNameForProperty($propertyName, 'get');
		$propertySetterName = $this->getMethodNameForProperty($propertyName, 'set');
		$propertyAdderName = $this->getMethodNameForProperty($propertyName, 'add');
		$propertyRemoverName = $this->getMethodNameForProperty($propertyName, 'remove');

		// check initial creation of property
		$this->assertInstanceOf(
			'Doctrine\Common\Collections\ArrayCollection',
			$this->fixture->{$propertyGetterName}(),
			'Class <' . get_class($this->fixture) . '> does not create a collection for property <' . $propertyName . '> right after creation'
		);

		// check add + chaining
		if ( in_array('add', $functionsToTest) ) {

			// check chaining
			$this->assertEquals(
				$this->fixture,
				$this->fixture->{$propertyAdderName}($item1),
				'Class <' . get_class($this->fixture) . '> does not enable chaining for property <' . $propertyName . '>, tried ' . $propertyAdderName . '()'
			);
			// works, so add second item
			$this->fixture->{$propertyAdderName}($item2);

			// check add
			$this->assertContains(
				$item1,
				$this->fixture->{$propertyGetterName}()
			);
			$this->assertContains(
				$item2,
				$this->fixture->{$propertyGetterName}()
			);
			$this->assertNotContains(
				$item3,
				$this->fixture->{$propertyGetterName}()
			);

		}

		// check remove + chaining
		if ( in_array('remove', $functionsToTest) ) {

			// check chaining
			$this->assertEquals(
				$this->fixture,
				$this->fixture->{$propertyRemoverName}($item2),
				'Class <' . get_class($this->fixture) . '> does not enable chaining for property <' . $propertyName . '>, tried ' . $propertyRemoverName . '()'
			);

			// check remove
			$this->assertContains(
				$item1,
				$this->fixture->{$propertyGetterName}()
			);
			$this->assertNotContains(
				$item2,
				$this->fixture->{$propertyGetterName}()
			);
			$this->assertNotContains(
				$item3,
				$this->fixture->{$propertyGetterName}()
			);

		}

		// check set + chaining
		if ( in_array('set', $functionsToTest) ) {

			// check chaining
			$this->assertEquals(
				$this->fixture,
				$this->fixture->{$propertySetterName}($itemCollection),
				'Class <' . get_class($this->fixture) . '> does not enable chaining for property <' . $propertyName . '>, tried ' . $propertySetterName . '()'
			);

			// check set
			$this->assertEquals(
				$itemCollection,
				$this->fixture->{$propertyGetterName}()
			);

		}
		
	}

	/**
	 * asserts that the get- and set- methods exist
	 * @param  string $propertyName   name of the property
	 * @param  array $functionsToTest functions to test
	 * @return void
	 */
	protected function assertPropertyFunctionsExist($propertyName, $functionsToTest) {

		$singularPropertyName = PluralizerUtility::singular($propertyName);

		if ( in_array('get', $functionsToTest) ) {

			// check if appropriate getter exists
			$propertyGetterName =  $this->getMethodNameForProperty($propertyName, 'get');
			$this->assertTrue(
				method_exists($this->fixture, $propertyGetterName),
				'Class <' . get_class($this->fixture) . '> has no getter for property <' . $propertyName . '>, tried ' . $propertyGetterName . '()'
			);
			
		}

		if ( in_array('set', $functionsToTest) ) {

			// check if appropriate setter exists
			$propertySetterName =  $this->getMethodNameForProperty($propertyName, 'set');
			$this->assertTrue(
				method_exists($this->fixture, $propertySetterName),
				'Class <' . get_class($this->fixture) . '> has no setter for property <' . $propertyName . '>, tried ' . $propertySetterName . '($value)'
			);
			
		}

		if ( in_array('add', $functionsToTest) ) {

			// check if appropriate adder exists
			$propertyAdderName = $this->getMethodNameForProperty($propertyName, 'add');
			$this->assertTrue(
				method_exists($this->fixture, $propertyAdderName),
				'Class <' . get_class($this->fixture) . '> has no adder for property <' . $singularPropertyName . '>, tried ' . $propertyAdderName . '($' . $singularPropertyName . 'ToAdd)'
			);
			
		}

		if ( in_array('remove', $functionsToTest) ) {

			// check if appropriate remover exists
			$propertyRemoverName =  $this->getMethodNameForProperty($propertyName, 'remove');
			$this->assertTrue(
				method_exists($this->fixture, $propertyRemoverName),
				'Class <' . get_class($this->fixture) . '> has no remover for property <' . $singularPropertyName . '>, tried ' . $propertyRemoverName . '($' . $singularPropertyName . 'ToRemove)'
			);

		}

	}

	/**
	 * returns the respective function name for the property
	 * 
	 * @param  string $propertyName name of the property
	 * @param  string $method       type of the function: get|set|add|remove
	 * @return string               name of the function
	 */
	protected function getMethodNameForProperty($propertyName, $method) {
		switch ($method) {
			case 'get':
				return 'get' . ucfirst($propertyName);
			case 'set':
				return 'set' . ucfirst($propertyName);
			case 'add':
				return 'add' . ucfirst(PluralizerUtility::singular($propertyName));
			case 'remove':
				return 'remove' . ucfirst(PluralizerUtility::singular($propertyName));
			default:
				throw new \Exception('Invalid method key: ' . $method);
				break;
		}
	}

}
?>