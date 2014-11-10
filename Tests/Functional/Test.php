<?php
namespace Econic\Testing\Tests\Functional;

use TYPO3\Flow\Tests\FunctionalTestCase;

/**
 * Main class for all other functional tests
 */
class Test extends FunctionalTestCase {

	/**
	 * @var \Econic\Testing\Factory\EntityFactory
	 */
	protected $entityFactory;

	public function setUp() {
		parent::setUp();
		// inject entity factory
		$this->entityFactory = $this->objectManager->get('Econic\Testing\Factory\EntityFactory');
	}

	public function tearDown() {
		parent::tearDown();
	}

}