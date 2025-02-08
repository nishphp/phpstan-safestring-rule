<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Accessory;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

class AccessorySafeStringType extends AccessoryLiteralStringType
{

	public function describe(VerbosityLevel $level): string
	{
		return 'safe-string';
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		if ($type instanceof self) {
			return AcceptsResult::createYes();
		}

		if ($type->isString()->yes() && count($type->getConstantStrings()) !== 0) {
			return AcceptsResult::createYes();
		}

		if ($type->isLiteralString()->yes()) {
			return AcceptsResult::createYes();
		}

		if ($type->isNumericString()->yes()) {
			return AcceptsResult::createYes();
		}

		return parent::accepts($type, $strictTypes);
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof self) {
			return IsSuperTypeOfResult::createYes();
		}

		return parent::isSubTypeOf($otherType);
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('safe-string');
	}

}
