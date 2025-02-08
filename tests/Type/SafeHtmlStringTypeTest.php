<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Generic\GenericClassStringType;
use Nish\PHPStan\Test\ClassWithToString;
use PHPStan\Type\Type;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\ObjectType;


class SafeHtmlStringTypeTest extends PHPStanTestCase
{
	public function setUp(): void
	{
        ReflectionProviderStaticAccessor::registerInstance($this->createReflectionProvider());
    }

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new Accessory\AccessorySafeHtmlStringType(),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createMaybe(),
			],
			[
				new Accessory\AccessorySafeHtmlStringType(),
				new ConstantStringType('foo'),
				TrinaryLogic::createYes(),
			],
			[
				new Accessory\AccessorySafeHtmlStringType(),
				new StringType(),
				TrinaryLogic::createMaybe(),
			],
			[
				new Accessory\AccessorySafeHtmlStringType(),
				new UnionType([
                    new StringType(),
                    new IntegerType(),
                ]),
				TrinaryLogic::createMaybe(),
			],
			[
				new Accessory\AccessorySafeHtmlStringType(),
				new Accessory\AccessorySafeHtmlStringType(),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(Accessory\AccessorySafeHtmlStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->isSuperTypeOf($otherType);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> isSuperTypeOf(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

	public function dataAccepts(): iterable
	{
        return [
            [
			new Accessory\AccessorySafeHtmlStringType(),
			new IntersectionType([
				new ObjectType(ClassWithToString::class),
				new HasPropertyType('foo'),
			]),
			TrinaryLogic::createNo(),
            ],
            [
                new Accessory\AccessorySafeHtmlStringType(),
                new ClassStringType(),
                TrinaryLogic::createMaybe(),
            ],
            [
                new Accessory\AccessorySafeHtmlStringType(),
                new StringType(),
                TrinaryLogic::createMaybe(),
            ],
        ];
	}

	/**
	 * @dataProvider dataAccepts
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testAccepts(Accessory\AccessorySafeHtmlStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true)->result;
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

}
