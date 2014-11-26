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
	 * @var \TYPO3\Flow\Mvc\Controller\MvcPropertyMappingConfigurationService
	 * @Flow\Inject
	 */
	protected $mvcPropertyMappingConfigurationService;

	/**
	 * @var \TYPO3\Flow\Security\Context
	 * @Flow\Inject
	 */
	protected $securityContext;

	/**
	 * All entities that are created by this factory
	 * @var array
	 */
	protected $managedEntities = array();

	/**
	 * Mocks an entity as wished
	 * 
	 * @param  string  $fqcn             the fully qualified class name
	 * @param  boolean $persist          if the entity should be directly persisted or not
	 * @param  array   $customProperties the properties to set if wished
	 * @return Object
	 */
	public function create($fqcn, $persist = false, $customProperties = array()) {
		$entityConfiguration = $this->entityConfiguration[ $fqcn ];

		$this->validateEntityConfiguration($fqcn, $entityConfiguration);

		// create from reflection class if constructor needs arguments
		if (!empty($entityConfiguration['constructorArguments'])) {
			$reflector = new \ReflectionClass( $fqcn );
			$constructorArguments = $this->getValuesFromConfigurations( $entityConfiguration['constructorArguments'] );
			$entity = $reflector->newInstanceArgs($constructorArguments);
		} else {
			$entity = new $fqcn();
		}

		// set the properties
		$configuredProperties = $entityConfiguration['properties'] ?: array();
		$properties = array_merge( $configuredProperties, $customProperties );
		foreach ($this->getValuesFromConfigurations($properties, $persist) as $propertyName => $propertyValue) {
			$propertyCouldBeSet = ObjectAccess::setProperty( $entity, $propertyName, $propertyValue );
			if (!$propertyCouldBeSet) {
				throw new \Exception($fqcn.'::$'.$propertyName.' could not be set to '.print_r($propertyValue, true), 1416481470);
			}
		}

		// persist if wished
		if ( $persist && is_string($entityConfiguration['repository']) ) {
			$this->objectManager->get( $entityConfiguration['repository'] )->add( $entity );

			// flush this entity here...
			$this->entityManager->flush($entity);

			// add to managed entities
			$identifier = $this->persistenceManager->getIdentifierByObject($entity);
			$this->managedEntities[ $identifier ] = $entity;
		}

		return $entity;
	}

	/**
	 * Validates the configuration and throws exceptions if invalid
	 * 
	 * @param  string $fqcn                the fully qualified class name
	 * @param  array  $entityConfiguration the entity configuration
	 * @return void
	 */
	protected function validateEntityConfiguration($fqcn, $entityConfiguration) {
		if ( !isset($entityConfiguration['repository']) ) {
			throw new \Exception('The entity of type ' . $fqcn . ' has no repository defined', 1416225586);
		}
	}

	/**
	 * Returns the final values from a given configuration
	 * 
	 * @param  array   $propertyConfigurations array with name => config
	 * @param  boolean $persistCreatedEntities if entities that must be created should be directly persisted
	 * @return array   name => value
	 */
	protected function getValuesFromConfigurations($propertyConfigurations, $persistCreatedEntities = false) {
		$properties = array();
		foreach ($propertyConfigurations as $propertyName => $propertyConfiguration) {

			// if it's an array and the __type is set, do fancy things...
			if (is_array($propertyConfiguration) && !empty($propertyConfiguration['__type'])) {
				switch ($propertyConfiguration['__type']) {

					// another entity
					case 'Entity':
						$properties[$propertyName] = $this->create($propertyConfiguration['fqcn'], $persistCreatedEntities);
						break;

					// a sha1 hash
					case 'sha1':
						$properties[$propertyName] = sha1(rand());
						break;

					// a datetime
					case 'DateTime':
						if (!empty($propertyConfiguration['time'])) {
							$properties[$propertyName] = new \DateTime( $propertyConfiguration['time'] );
						} else {
							$properties[$propertyName] = new \DateTime();
						}
						break;

					// unknown type, throw exception
					default:
						throw new \Exception('type <' . $propertyConfiguration['__type'] . '> of ' . $fqcn . '::' . $propertyName . ' is unknown', 1415281345);
						break;
				}
			}
			// otherwise just assign the value
			else {
				$properties[$propertyName] = $propertyConfiguration;
			}
		}
		return $properties;
	}

	/**
	 * Creates an entity as an array that you can submit as if you used a form
	 * 
	 * @param  string  $argumentName                argument name
	 * @param  string  $fqcn                        the fully qualified class name
	 * @param  array   $customProperties            the properties to set if wished
	 * @param  array   $additionalTrustedProperties more properties to be trusted
	 * @return array  the argument you can then submit
	 */
	public function getSubmitArgumentsForNewEntity($argumentName, $fqcn, $customProperties = array(), $additionalTrustedProperties = array()) {

		$entityConfiguration = $this->entityConfiguration[ $fqcn ];
		$arguments = array( $argumentName => array() );
		$propertyNamesForMappingService = $additionalTrustedProperties;

		// set the properties
		$properties = array_merge( $entityConfiguration['properties'], $customProperties );
		foreach ($properties as $propertyName => $propertyValue) {

			// if it's an array and the __type is set, ignore...
			if (is_array($propertyValue) && !empty($propertyValue['__type'])) {
				// ignore property
			} else {
				$arguments[ $argumentName ][ $propertyName ] = $propertyValue;
				$propertyNamesForMappingService[] = $argumentName . '[' . $propertyName . ']';
			}
		}

		$propertyNamesForMappingService[] = '';

		// add __trustedProperties
		$arguments[ '__trustedProperties' ] = $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($propertyNamesForMappingService, '');

		// add __csrfToken
		$arguments[ '__csrfToken' ] = $this->securityContext->getCsrfProtectionToken();

		return $arguments;
	}

	/**
	 * Creates an entity as an array that you can submit as if you used a form
	 * 
	 * @param  string $argumentName                the name
	 * @param  Object $persistedEntity             the Entity that you'd like to create this argument from
	 * @param  array  $customProperties            the properties to set if wished
	 * @param  array  $additionalTrustedProperties more properties to be trusted
	 * @return array  the argument you can then submit
	 */
	public function getSubmitArgumentsForPersistedEntity($argumentName, $persistedEntity, $customProperties = array(), $additionalTrustedProperties = array()) {

		$arguments = array( $argumentName => $this->getIdentityArgumentFromPersistedEntity($persistedEntity) );
		$propertyNamesForMappingService = array($argumentName.'[__identity]') + $additionalTrustedProperties;

		// set the properties
		foreach ($customProperties as $propertyName => $propertyValue) {
			$arguments[ $argumentName ][ $propertyName ] = $propertyValue;
			$propertyNamesForMappingService[] = $argumentName . '[' . $propertyName . ']';
		}

		$propertyNamesForMappingService[] = '';

		// add __trustedProperties
		$arguments[ '__trustedProperties' ] = $this->mvcPropertyMappingConfigurationService->generateTrustedPropertiesToken($propertyNamesForMappingService, '');

		// add __csrfToken
		$arguments[ '__csrfToken' ] = $this->securityContext->getCsrfProtectionToken();

		return $arguments;
	}

	/**
	 * Returns an array with an __identity entry for submitting somewhere
	 * 
	 * @param  Object $persistedEntity the Entity you want to submit
	 * @return array  the argument you can then submit
	 */
	public function getIdentityArgumentFromPersistedEntity($persistedEntity) {
		return array(
			'__identity' => $this->persistenceManager->getIdentifierByObject($persistedEntity)
		);
	}

	public function refreshAllEntities() {
		foreach ($this->managedEntities as $identifier => $entity) {
			$this->entityManager->refresh($entity);
		}
	}

	public function refreshEntity($entity) {
		if (!in_array($entity, $this->managedEntities)) {
			throw new \Exception('The entity to be refreshed is not handled by the entity factory', 1416484011);
		}

		$this->entityManager->refresh($entity);
	}

	public function flushEntity($entity) {
		$this->entityManager->flush($entity);
	}

}