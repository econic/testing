<?php
namespace Econic\Testing\Tests\Functional;

use Econic\Testing\Tests\Functional\Test;
use TYPO3\Flow\Http\Response;

/**
 * Proxy class for a response to make further assertions
 */
class ResponseProxy {

	/**
	 * test
	 * 
	 * @var Test
	 */
	protected $test;

	/**
	 * response
	 * 
	 * @var Response
	 */
	protected $response;

	public function __construct(Test $test, Response $response) {
		$this->test = $test;
		$this->response = $response;
	}

	public function getResponse() {
		return $this->response;
	}

	public function getContent() {
		return $this->response->getContent();
	}

	public function hasStatusCode($expectedStatusCode) {
		$realStatusCode = $this->response->getStatusCode();
		$this->test->assertSame(
			$realStatusCode,
			$expectedStatusCode,
			'Status Code was ' . $realStatusCode . ' (' . $this->response->getStatusMessageByCode($realStatusCode) . ') instead of ' . $expectedStatusCode . ':' . PHP_EOL . $this->response->getContent()
		);
		return $this;
	}

	public function contains($string) {
		$this->test->assertContains(
			$string,
			$this->response->getContent(),
			'The Response did not contain the test string <' . $string . '>'
		);
		return $this;
	}

}