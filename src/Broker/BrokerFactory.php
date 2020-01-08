<?php declare(strict_types = 1);

namespace Nish\PHPStan\Broker;

use PHPStan\DependencyInjection\Container;
use Nish\PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Broker\Broker;

class BrokerFactory extends \PHPStan\Broker\BrokerFactory
{
	public function __construct(Container $container)
	{
		parent::__construct($container);

        // replace original sprintf extension
        // TODO: other way
        \Closure::bind(function(){
            /** @var \PHPStan\DependencyInjection\Nette\NetteContainer $netteContainer */
            $netteContainer = $this;

            /** @var \Nette\DI\Container */
            $container = $netteContainer->container;

            $tags = $container->findByTag(\PHPStan\Broker\BrokerFactory::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG);
            foreach (array_keys($tags) as $tag){
                $s = $container->getService($tag);
                if ($s instanceof \PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension){
                    $container->removeService($tag);
                    $container->addService($tag, new \Nish\PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension());
                    break;
                }
            }
        }, $container, \PHPStan\DependencyInjection\Nette\NetteContainer::class)->__invoke();
	}
}
