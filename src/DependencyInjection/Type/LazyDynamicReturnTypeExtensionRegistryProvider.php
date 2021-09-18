<?php

declare(strict_types = 1);

namespace Nish\PHPStan\DependencyInjection\Type;

use PHPStan\Broker\Broker;
use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicReturnTypeExtensionRegistry;

class LazyDynamicReturnTypeExtensionRegistryProvider implements DynamicReturnTypeExtensionRegistryProvider
{

	/** @var \PHPStan\DependencyInjection\Container */
	private $container;

	/** @var ?\PHPStan\Type\DynamicReturnTypeExtensionRegistry */
	private $registry = null;

	public function __construct(\PHPStan\DependencyInjection\Container $container)
	{
		$this->container = $container;
	}

	public function getRegistry(): DynamicReturnTypeExtensionRegistry
	{
		if ($this->registry === null) {
			$extensions = $this->container->getServicesByTag(BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG);

			usort($extensions, static function ($a, $b) {
				if (strpos(get_class($a), 'Nish\\', 0) !== false) {
					return -1;
				}
				if (strpos(get_class($b), 'Nish\\', 0) !== false) {
					return 1;
				}
				return 0;
			});

			$this->registry = new DynamicReturnTypeExtensionRegistry(
				$this->container->getByType(Broker::class),
				$this->container->getByType(ReflectionProvider::class),
				$this->container->getServicesByTag(BrokerFactory::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG),
				$this->container->getServicesByTag(BrokerFactory::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG),
				$extensions
			);
		}

		return $this->registry;
	}

}
