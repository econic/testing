<?php
namespace Econic\Testing\Factory;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Reflection\ObjectAccess;

/**
 * Factory for creating entities from configuration
 */
class EntityFactory {

	/**
	 * @var array
	 * @Flow\Inject(setting="entityConfiguration")
	 */
	protected $entityConfiguration;

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
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService
	 */
	protected $mvcPropertyMappingConfigurationService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Context
	 */
	protected $securityContext;

	/**
	 * Mocks an entity as wished
	 * 
	 * @param  array   $fqcn             the fully qualified class name
	 * @param  boolean $persist          if the entity should be persisted or not
	 * @param  array   $customProperties the properties to set if wished
	 * @return Object
	 */
	public function create($fqcn, $persist = false, $customProperties = array()) {

		$entityConfiguration = $this->entityConfiguration[ $fqcn ];
		$entity = new $fqcn();

		// set the properties
		$properties = array_merge( $entityConfiguration['properties'], $customProperties );
		foreach ($properties as $propertyName => $propertyValue) {

			// if it's an array and the __type is set, do fancy things...
			if (is_array($propertyValue) && !empty($propertyValue['__type'])) {
				switch ($propertyValue['__type']) {

					// another entity
					case 'Entity':
						$propertyValue = $this->create($propertyValue['fqcn'], $persist);
						break;

					// a datetime
					case 'DateTime':
						$propertyValue = new \DateTime();
						break;

					// unknown type, throw exception
					default:
						throw new \Exception('type <' . $propertyValue['__type'] . '> of ' . $fqcn . '::' . $propertyName . ' is unknown', 1415281345);
						break;
				}
			}
			
			// set the value
			ObjectAccess::setProperty( $entity, $propertyName, $propertyValue );
		}

		// persist if wished
		if ($persist) {
			$this->objectManager->get( $entityConfiguration['repository'] )->add( $entity );
			$this->persistenceManager->persistAll();
		}

		return $entity;
	}

	/**
	 * Creates an entity as an array that you can submit as if you used a form
	 * 
	 * @param  array   $string           argument name
	 * @param  array   $fqcn             the fully qualified class name
	 * @param  array   $customProperties the properties to set if wished
	 * @return array
	 */
	public function getSubmittableArray($argumentName, $fqcn, $customProperties = array()) {

		$entityConfiguration = $this->entityConfiguration[ $fqcn ];
		$array = array( $argumentName => array() );
		$propertyNamesForMappingService = array();

		// set the properties
		$properties = array_merge( $entityConfiguration['properties'], $customProperties );
		foreach ($properties as $propertyName => $propertyValue) {

			// if it's an array and the __type is set, ignore...
			if (is_array($propertyValue) && !empty($propertyValue['__type'])) {
				// ignore property
			} else {
				$array[ $argumentName ][ $propertyName ] = $propertyValue;
				$propertyNamesForMappingService[] = $argumentName . '[' . $propertyName . ']';
			}
		}

		$propertyNamesForMappingService[] = '';

		// add __trustedProperties
		$array[ '__trustedProperties' ] = $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($propertyNamesForMappingService, '');

		// add __csrfToken
		// $array[ '__csrfToken' ] = $this->securityContext->getCsrfProtectionToken();

		return $array;
	}

	public function getSubmittableArrayFromPersistedEntity($argumentName, $existingEntity, $customProperties = array()) {
		return $this->getSubmittableArray(
			$argumentName,
			get_class($existingEntity),
			array('__identity' => $this->persistenceManager->getIdentifierByObject($existingEntity)) + $customProperties
		);
	}

}