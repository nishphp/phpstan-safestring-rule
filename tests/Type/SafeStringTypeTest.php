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


class StringTypeTest extends TestCase
{

	public function dataIsSuperTypeOf(): array
	{
		return [
			[
				new SafeStringType(),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createNo(),
			],
			[
				new SafeStringType(),
				new ConstantStringType('foo'),
				TrinaryLogic::createYes(),
			],
			[
				new SafeStringType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new SafeStringType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new SafeStringType(),
				new UnionType([
                    new StringType(),
                    new IntegerType(),
                ]),
				TrinaryLogic::createNo(),
			],
			[
				new SafeStringType(),
				new SafeStringType(),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(SafeStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
			new SafeStringType(),
			new IntersectionType([
				new ObjectType(ClassWithToString::class),
				new HasPropertyType('foo'),
			]),
			TrinaryLogic::createNo(),
            ],
            [
                new SafeStringType(),
                new ClassStringType(),
                TrinaryLogic::createNo(),
            ],
            [
                new SafeStringType(),
                new ConstantStringType('foo'),
                TrinaryLogic::createYes(),
            ],
            [
                new SafeStringType(),
                new StringType(),
                TrinaryLogic::createNo(),
            ],
            [
                new SafeStringType(),
                new SafeStringType(),
                TrinaryLogic::createYes(),
            ],
        ];
	}

	/**
	 * @dataProvider dataAccepts
	 * @param \Nish\PHPStan\Type\SafeStringType $type
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testAccepts(SafeStringType $type, Type $otherType, TrinaryLogic $expectedResult): void
	{
		$actualResult = $type->accepts($otherType, true);
		$this->assertSame(
			$expectedResult->describe(),
			$actualResult->describe(),
			sprintf('%s -> accepts(%s)', $type->describe(VerbosityLevel::precise()), $otherType->describe(VerbosityLevel::precise()))
		);
	}

    public function testIsCallable()
    {
        $type = new SafeStringType();
        $ret = $type->isCallable();
        $this->assertSame(
            $ret->describe(),
            TrinaryLogic::createNo()->describe(),
            sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise()))
        );
    }

    public function testToInt()
    {
        $type = new SafeStringType();
        $ret = $type->toInteger();
        $this->assertSame(
            $ret->describe(VerbosityLevel::precise()),
            (new ErrorType())->describe(VerbosityLevel::precise()),
            sprintf('%s -> toInteger()', $type->describe(VerbosityLevel::precise()))
        );
    }

    public function testToFloat()
    {
        $type = new SafeStringType();
        $ret = $type->toFloat();
        $this->assertSame(
            $ret->describe(VerbosityLevel::precise()),
            (new ErrorType())->describe(VerbosityLevel::precise()),
            sprintf('%s -> toFloat()', $type->describe(VerbosityLevel::precise()))
        );
    }
    public function testToArray()
    {
        $type = new SafeStringType();
        $ret = $type->toArray();
        $this->assertSame(
            $ret->describe(VerbosityLevel::precise()),
            (new ErrorType())->describe(VerbosityLevel::precise()),
            sprintf('%s -> toArray()', $type->describe(VerbosityLevel::precise()))
        );
    }
}
