<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Generic\GenericClassStringType;
use Nish\PHPStan\Test\ClassWithToString;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Type;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\ObjectType;


class StringTypeTest extends PHPStanTestCase
{
	public function setUp(): void
	{
        ReflectionProviderStaticAccessor::registerInstance($this->createReflectionProvider());
    }

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new Accessory\AccessorySafeStringType(),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createNo(),
			],
			[
				new Accessory\AccessorySafeStringType(),
				new ConstantStringType('foo'),
				TrinaryLogic::createYes(),
			],
			[
				new Accessory\AccessorySafeStringType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new Accessory\AccessorySafeStringType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new Accessory\AccessorySafeStringType(),
				new UnionType([
                    new StringType(),
                    new IntegerType(),
                ]),
				TrinaryLogic::createNo(),
			],
			[
				new Accessory\AccessorySafeStringType(),
				new Accessory\AccessorySafeStringType(),
				TrinaryLogic::createYes(),
			],
            [
                new Accessory\AccessorySafeStringType(),
                new IntersectionType([
                    new StringType(),
                    new AccessoryLiteralStringType(),
                ]),
                TrinaryLogic::createYes(),
            ],
            [
                new Accessory\AccessorySafeStringType(),
                new IntersectionType([
                    new StringType(),
                    new AccessoryNumericStringType(),
                ]),
                TrinaryLogic::createYes(),
            ],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(Accessory\AccessorySafeStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
                new Accessory\AccessorySafeStringType(),
                new IntersectionType([
                    new ObjectType(ClassWithToString::class),
                    new HasPropertyType('foo'),
                ]),
                TrinaryLogic::createNo(),
            ],
            [
                new Accessory\AccessorySafeStringType(),
                new ClassStringType(),
                TrinaryLogic::createNo(),
            ],
            [
                new Accessory\AccessorySafeStringType(),
                new ConstantStringType('foo'),
                TrinaryLogic::createYes(),
            ],
            [
                new Accessory\AccessorySafeStringType(),
                new StringType(),
                TrinaryLogic::createNo(),
            ],
            [
                new Accessory\AccessorySafeStringType(),
                new Accessory\AccessorySafeStringType(),
                TrinaryLogic::createYes(),
            ],
            [
                new Accessory\AccessorySafeStringType(),
                new IntersectionType([
                    new StringType(),
                    new AccessoryLiteralStringType(),
                ]),
                TrinaryLogic::createYes(),
            ],
        ];
	}

	/**
	 * @dataProvider dataAccepts
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testAccepts(Accessory\AccessorySafeStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true)->result;
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

    public function testIsCallable()
    {
        $type = new Accessory\AccessorySafeStringType();
        $ret = $type->isCallable();
        $this->assertSame(
            TrinaryLogic::createNo()->describe(),
            $ret->describe(),
            sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise()))
        );
    }

    public function testToInt()
    {
        $type = new Accessory\AccessorySafeStringType();
        $ret = $type->toInteger();
        $this->assertSame(
            (new IntegerType())->describe(VerbosityLevel::precise()),
            $ret->describe(VerbosityLevel::precise()),
            sprintf('%s -> toInteger()', $type->describe(VerbosityLevel::precise()))
        );
    }

    public function testToFloat()
    {
        $type = new Accessory\AccessorySafeStringType();
        $ret = $type->toFloat();
        $this->assertSame(
            (new FloatType())->describe(VerbosityLevel::precise()),
            $ret->describe(VerbosityLevel::precise()),
            sprintf('%s -> toFloat()', $type->describe(VerbosityLevel::precise()))
        );
    }
    public function testToArray()
    {
        $type = new Accessory\AccessorySafeStringType();
        $ret = $type->toArray();
        $this->assertSame(
            (new ConstantArrayType([new ConstantIntegerType(0)], [new Accessory\AccessorySafeStringType()], [1], []))->describe(VerbosityLevel::precise()),
            $ret->describe(VerbosityLevel::precise()),
            sprintf('%s -> toArray()', $type->describe(VerbosityLevel::precise()))
        );
    }
}
