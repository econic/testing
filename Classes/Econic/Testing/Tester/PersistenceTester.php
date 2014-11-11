<?php
namespace Econic\Testing\Tester;

use TYPO3\Flow\Annotations as Flow;
use Econic\Testing\Tests\Functional\Test;

/**
 * Testing class to make presistence related assertions
 */
class PersistenceTester {

	/**
	 * @var Test
	 */
	protected $test;

	/**
	 * @var \TYPO3\Flow\Object\ObjectManagerInterface
	 * @Flow\Inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\Flow\Persistence\PersistenceManagerInterface
	 * @Flow\Inject
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\Flow\Persistence\Generic\Session
	 * @Flow\Inject
	 */
	protected $persistenceSession;

	/**
	 * @var array
	 * @Flow\Inject(setting="entityConfiguration")
	 */
	protected $entityConfiguration;

	public function __construct(Test $test) {
		$this->test = $test;
	}

	public function assertPersisted($entity) {
		$this->test->assertFalse(
			$this->persistenceManager->isNewObject($entity),
			'The Entity of type ' . get_class($entity) . ' is not persisted.'
		);
		return $this;
	}

	public function assertUnpersisted($entity) {
		$this->test->assertTrue(
			$this->persistenceManager->isNewObject($entity),
			'The Entity of type ' . get_class($entity) . ' is still persisted.'
		);
		return $this;
	}

	public function assertCount($fqcn, $expectedCount) {
		$realCount = $this->objectManager->get( $this->entityConfiguration[ $fqcn ][ 'repository' ] )->countAll();
		$this->test->assertSame(
			$expectedCount,
			$realCount,
			'There are ' . $realCount . ' entities persisted instead of ' . $expectedCount . ' of type ' . $fqcn
		);
		return $this;
	}

	public function assertPersistedProperty($entity, $propertyName, $expectedPropertyValue = null) {
		if ($expectedPropertyValue === null) {
			$this->test->assertTrue(
				$this->persistenceSession->isDirty( $entity, $propertyName ),
				'The property ' . $propertyName . ' is not persisted'
			);
		} else {
			$realPropertyValue = $this->persistenceSession->getCleanStateOfProperty( $entity, $propertyName );
			$this->test->assertSame(
				$realPropertyValue,
				$expectedPropertyValue,
				'The value of property ' . $propertyName . ' is ' . $realPropertyValue . ' instead of ' . $expectedPropertyValue
			);
		}
		return $this;
	}

}