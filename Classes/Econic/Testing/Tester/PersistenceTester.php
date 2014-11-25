<?php
namespace Econic\Testing\Tester;

use TYPO3\Flow\Annotations as Flow;
use Econic\Testing\Tests\Functional\Test;
use TYPO3\Flow\Reflection\ObjectAccess;

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
	 * @var \Doctrine\Common\Persistence\ObjectManager
	 * @Flow\Inject
	 */
	protected $entityManager;

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

	public function assertCleanProperty($entity, $propertyName, $forceDirectAccess = true) {
		$currentPropertyValue = ObjectAccess::getProperty($entity, $propertyName, $forceDirectAccess);
		$this->entityManager->refresh($entity);
		$persistedPropertyValue = ObjectAccess::getProperty($entity, $propertyName, $forceDirectAccess);

		$this->test->assertSame(
			$currentPropertyValue,
			$persistedPropertyValue,
			'The property ' . $propertyName . ' was not clean'
		);
		return $this;
	}

	public function assertPersistedPropertyValue($entity, $propertyName, $expectedPropertyValue, $forceDirectAccess = true) {
		$this->entityManager->refresh($entity);
		$persistedPropertyValue = ObjectAccess::getProperty($entity, $propertyName, $forceDirectAccess);

		$this->test->assertSame(
			$expectedPropertyValue,
			$persistedPropertyValue,
			'The property ' . $propertyName . ' did not have the expected persistent value'
		);
		return $this;
	}

	public function findAllOfType($fqcn) {
		return $this->objectManager->get( $this->entityConfiguration[ $fqcn ][ 'repository' ] )->findAll();
	}

}