<?php
namespace Econic\Testing\Tests\Functional\Controller;

use Econic\Testing\Tests\Functional\Test;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;
use Econic\Testing\Tests\Functional\ResponseProxy;

/**
 * Functional test class for all controllers
 * A persisted HV is always available
 */
abstract class ControllerTest extends Test {

	protected $packageKey;
	protected $subpackageKey;
	protected $controllerName;

	protected $request_subdomain = null;
	protected $request_method = 'GET';
	protected $request_uri = '/';
	protected $request_port = '80';
	protected $request_protocol = 'http:';
	protected $request_domain = 'localhost';

	protected $testableSecurityEnabled = true;
	static protected $testablePersistenceEnabled = true;

	/**
	 * @var \TYPO3\Flow\Mvc\Routing\UriBuilder
	 */
	protected $uriBuilder;

	/**
	 * @var \TYPO3\Flow\Mvc\ActionRequest
	 */
	protected $referringRequest = null;


	public function setUp() {
		parent::setUp();

		// inject application context
		$this->uriBuilder = $this->objectManager->get('TYPO3\Flow\Mvc\Routing\UriBuilder');
	}

	public function tearDown() {
		parent::tearDown();
	}

	protected function request($actionName, $method = 'GET', array $arguments = array(), array $files = array(), array $server = array(), $content = NULL) {
		$this->buildReferringRequest();

		// initialize uri builder
		$this->uriBuilder
			->reset()
			->setRequest($this->referringRequest);

		// create relative uri
		switch ($method) {
			case 'GET':

				// build uri
				$uri = $this->buildAbsoluteUri(
					$this->uriBuilder->uriFor(
						$actionName,
						$arguments,
						$this->controllerName,
						$this->packageKey,
						$this->subpackageKey
					)
				);

				// send post request
				$reponse = $this->browser->request( $uri, $method, array(), $files, $server, $content );
				break;

			case 'POST':

				// build uri
				$uri = $this->buildAbsoluteUri(
					$this->uriBuilder->uriFor(
						$actionName,
						array(),
						$this->controllerName,
						$this->packageKey,
						$this->subpackageKey
					)
				);

				// convert entities to identity arguments, because the browser only accepts simple types in a post request
				$convertedArguments = array();
				foreach ($arguments as $argumentName => $argumentValue) {
					if (is_object($argumentValue)) {
						$convertedArguments[$argumentName] = $this->entityFactory->getIdentityArgumentFromPersistedEntity($argumentValue);
					} else {
						$convertedArguments[$argumentName] = $argumentValue;
					}
				}

				// send post request
				$reponse = $this->browser->request( $uri, $method, $convertedArguments, $files, $server, $content );
				break;

			default:
				throw new \Exception('method ' . $method . ' is not supported', 1415366761);
				break;
		}

		return new ResponseProxy( $this, $reponse );
	}

	protected function buildReferringRequest($overwrite = false) {
		// skip if there's a request that shouldn't be overridden
		if ($overwrite === false && $this->referringRequest !== null) {
			return;
		}

		$serverConfiguration = array(
			'REQUEST_METHOD' => $this->request_method,
			'HTTP_HOST' => $this->request_domain,
			'REQUEST_URI' => $this->request_uri,
			'SERVER_PORT' => $this->request_port,

			'REDIRECT_FLOW_CONTEXT' => 'Development',
			'REDIRECT_FLOW_REWRITEURLS' => '1',
			'REDIRECT_STATUS' => '200',
			'FLOW_CONTEXT' => 'Testing',
			'FLOW_REWRITEURLS' => '1',
			'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/534.52.7 (KHTML, like Gecko) Version/5.1.2 Safari/534.52.7',
			'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			'HTTP_ACCEPT_LANGUAGE' => 'en-us',
			'HTTP_ACCEPT_ENCODING' => 'gzip, deflate',
			'HTTP_CONNECTION' => 'keep-alive',
			'PATH' => '/usr/bin:/bin:/usr/sbin:/sbin',
			'SERVER_SIGNATURE' => '',
			'SERVER_SOFTWARE' => 'Apache/2.2.21 (Unix) mod_ssl/2.2.21 OpenSSL/1.0.0e DAV/2 PHP/5.3.8',
			'SERVER_NAME' => 'localhost',
			'SERVER_ADDR' => '127.0.0.1',
			'REMOTE_ADDR' => '127.0.0.1',
			'DOCUMENT_ROOT' => '/opt/local/apache2/htdocs/',
			'SERVER_ADMIN' => 'george@localhost',
			'SCRIPT_FILENAME' => '/opt/local/apache2/htdocs/Web/index.php',
			'REMOTE_PORT' => '51439',
			'REDIRECT_QUERY_STRING' => '',
			'REDIRECT_URL' => '',
			'GATEWAY_INTERFACE' => 'CGI/1.1',
			'SERVER_PROTOCOL' => 'HTTP/1.1',
			'SCRIPT_NAME' => '/index.php',
			'PHP_SELF' => '/index.php',
			'REQUEST_TIME' => 1326472534,
		);
		$this->referringRequest = (new \TYPO3\Flow\Http\Request( array(), array(), array(), $serverConfiguration ))->createActionRequest();
	}

	protected function buildAbsoluteUri($relativeUri) {
		return $this->request_protocol . '//' . $this->request_subdomain . '.' . $this->request_domain . $relativeUri;
	}

}