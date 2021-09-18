<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PHPStan\Testing\TestCase;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;

class RuleHelperTest extends TestCase
{

	public function testAcceptsScolor(): void
	{
		$this->assertTrue(RuleHelper::accepts(new BooleanType()));
		$this->assertTrue(RuleHelper::accepts(new IntegerType()));
		$this->assertTrue(RuleHelper::accepts(new ConstantStringType('a')));
	}

	public function testAcceptsArray(): void
	{
		$this->assertTrue(RuleHelper::accepts(new ArrayType(new IntegerType(), new IntegerType())));
	}

	public function testAcceptsCompound(): void
	{
		$this->assertTrue(RuleHelper::accepts(new IntersectionType([
			new StringType(), new AccessoryLiteralStringType(),
		])));

		$this->assertTrue(RuleHelper::accepts(new UnionType([
			new IntegerType(), new BooleanType(),
		])));
	}

	public function testAcceptsArrayCompound(): void
	{
		$this->assertTrue(RuleHelper::accepts(
			new ArrayType(new IntegerType(), new IntersectionType([
				new StringType(), new AccessoryLiteralStringType(),
			]))
		));
	}

	public function testAcceptsCompoundArray(): void
	{
		$this->assertTrue(RuleHelper::accepts(new IntersectionType([
			new ArrayType(new IntegerType(), new IntegerType()),
			new NonEmptyArrayType(),
		])));
	}

	public function testNotAcceptsString(): void
	{
		$this->assertFalse(RuleHelper::accepts(new StringType()));
		$this->assertFalse(RuleHelper::accepts(new IntersectionType([
			new StringType(), new AccessoryNonEmptyStringType(),
		])));
	}

	public function testNotAcceptsCompound(): void
	{
		$this->assertFalse(RuleHelper::accepts(new UnionType([
			new IntegerType(),
			new StringType(),
		])));
	}

}
