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
				new SafeHtmlType(),
				new GenericClassStringType(new ObjectType(\Exception::class)),
				TrinaryLogic::createYes(),
			],
			[
				new SafeHtmlType(),
				new ConstantStringType('foo'),
				TrinaryLogic::createYes(),
			],
			[
				new SafeHtmlType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new SafeHtmlType(),
				new StringType(),
				TrinaryLogic::createNo(),
			],
			[
				new SafeHtmlType(),
				new UnionType([
                    new StringType(),
                    new IntegerType(),
                ]),
				TrinaryLogic::createNo(),
			],
			[
				new SafeHtmlType(),
				new SafeHtmlType(),
				TrinaryLogic::createYes(),
			],
		];
	}

	/**
	 * @dataProvider dataIsSuperTypeOf
	 */
	public function testIsSuperTypeOf(SafeHtmlType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
			new SafeHtmlType(),
			new IntersectionType([
				new ObjectType(ClassWithToString::class),
				new HasPropertyType('foo'),
			]),
			TrinaryLogic::createNo(),
            ],
            [
                new SafeHtmlType(),
                new ClassStringType(),
                TrinaryLogic::createYes(),
            ],
            [
                new SafeHtmlType(),
                new StringType(),
                TrinaryLogic::createYes(),
            ],
        ];
	}

	/**
	 * @dataProvider dataAccepts
	 * @param \Nish\PHPStan\Type\SafeHtmlType $type
	 * @param Type $otherType
	 * @param TrinaryLogic $expectedResult
	 */
	public function testAccepts(SafeHtmlType $type, Type $otherType, TrinaryLogic $expectedResult): void
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
        $type = new SafeHtmlType();
        $ret = $type->isCallable();
        $this->assertSame(
            $ret->describe(),
            TrinaryLogic::createNo()->describe(),
            sprintf('%s -> isCallable()', $type->describe(VerbosityLevel::precise()))
        );
    }

    public function testToInt()
    {
        $type = new SafeHtmlType();
        $ret = $type->toInteger();
        $this->assertSame(
            $ret->describe(VerbosityLevel::precise()),
            (new ErrorType())->describe(VerbosityLevel::precise()),
            sprintf('%s -> toInteger()', $type->describe(VerbosityLevel::precise()))
        );
    }

    public function testToFloat()
    {
        $type = new SafeHtmlType();
        $ret = $type->toFloat();
        $this->assertSame(
            $ret->describe(VerbosityLevel::precise()),
            (new ErrorType())->describe(VerbosityLevel::precise()),
            sprintf('%s -> toFloat()', $type->describe(VerbosityLevel::precise()))
        );
    }
    public function testToArray()
    {
        $type = new SafeHtmlType();
        $ret = $type->toArray();
        $this->assertSame(
            $ret->describe(VerbosityLevel::precise()),
            (new ErrorType())->describe(VerbosityLevel::precise()),
            sprintf('%s -> toArray()', $type->describe(VerbosityLevel::precise()))
        );
    }
}
