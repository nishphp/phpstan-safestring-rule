<?php declare(strict_types = 1);

namespace Nish\PHPStan\PhpDoc;

use Nish\PHPStan\Type\Accessory\AccessorySafeStringType;
use PHPStan\PhpDoc\TypeStringResolver;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\UnionType;
use PHPStan\Type\StringType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\PhpDoc\TypeNodeResolver;
use Closure;
use PHPStan\DependencyInjection\Container;
use PHPStan\PhpDoc\TypeNodeResolverExtensionRegistryProvider;
use PHPStan\PhpDoc\TypeNodeResolverExtensionAwareRegistry;
use PHPStan\PhpDoc\TypeNodeResolverExtension;

class TypeDescriptionTest extends PHPStanTestCase
{

    public function setUp(): void
    {
        $container = self::getContainer();
        $resolver = $container
                  ->getByType(TypeNodeResolverExtensionRegistryProvider::class);
        Closure::bind(function() use ($container) {
            $this->registry
                = new TypeNodeResolverExtensionAwareRegistry(
                    $container->getByType(TypeNodeResolver::class),
                    $container->getServicesByTag(TypeNodeResolverExtension::EXTENSION_TAG)
                        + [new \Nish\PHPStan\PhpDoc\TypeNodeResolverExtension()],

                );
        }, $resolver, $resolver::class)->__invoke();
    }

	public function dataTest(): iterable
	{
		yield ['non-empty-string', new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()])];
		yield ['safe-string', new IntersectionType([new StringType(), new AccessorySafeStringType()])];
		yield ['string&safe-string', new IntersectionType([new StringType(), new AccessorySafeStringType()])];
		yield ['non-falsy-string&safe-string', new IntersectionType([new StringType(), new AccessoryNonFalsyStringType(), new AccessorySafeStringType()])];
		yield ["''|(non-falsy-string&safe-string)", new UnionType([
            new IntersectionType([new StringType(), new AccessoryNonFalsyStringType(), new AccessorySafeStringType()])
            , new ConstantStringType('')
        ])];
	}

	/**
	 * @dataProvider dataTest
	 */
	public function testParsingDesiredTypeDescription(string $description, Type $expectedType): void
	{
		$typeStringResolver = self::getContainer()->getByType(TypeStringResolver::class);
		$type = $typeStringResolver->resolve($description);
        // var_dump(['expected' => $expectedType, 'actual' => $type]);
        // var_dump($expectedType->describe(VerbosityLevel::precise()),$type->describe(VerbosityLevel::precise()));
		$this->assertTrue($expectedType->equals($type), sprintf('Parsing %s did not result in %s, but in %s', $description, $expectedType->describe(VerbosityLevel::value()), $type->describe(VerbosityLevel::value())));

		$newDescription = $type->describe(VerbosityLevel::value());
		$newType = $typeStringResolver->resolve($newDescription);
		$this->assertTrue($type->equals($newType), sprintf('Parsing %s again did not result in %s, but in %s', $newDescription, $type->describe(VerbosityLevel::value()), $newType->describe(VerbosityLevel::value())));
	}

}
