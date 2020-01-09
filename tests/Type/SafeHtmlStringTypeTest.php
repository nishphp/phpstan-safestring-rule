<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PHPStan\Testing\TestCase;
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


class SafeHtmlStringTypeTest extends TestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new SafeHtmlStringType(),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createYes(),
			],
			[
				new SafeHtmlStringType(),
				new ConstantStringType('foo'),
				TrinaryLogic::createYes(),
			],
			[
				new SafeHtmlStringType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new SafeHtmlStringType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new SafeHtmlStringType(),
				new UnionType([
                    new StringType(),
                    new IntegerType(),
                ]),
				TrinaryLogic::createNo(),
			],
			[
				new SafeHtmlStringType(),
				new SafeHtmlStringType(),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(SafeHtmlStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
			new SafeHtmlStringType(),
			new IntersectionType([
				new ObjectType(ClassWithToString::class),
				new HasPropertyType('foo'),
			]),
			TrinaryLogic::createNo(),
            ],
            [
                new SafeHtmlStringType(),
                new ClassStringType(),
                TrinaryLogic::createYes(),
            ],
            [
                new SafeHtmlStringType(),
                new StringType(),
                TrinaryLogic::createNo(),
            ],
        ];
	}

	/**
	 * @dataProvider dataAccepts
	 * @param \Nish\PHPStan\Type\SafeHtmlStringType $type
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testAccepts(SafeHtmlStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

}
