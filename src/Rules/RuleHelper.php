<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class RuleHelper
{

	public static function acceptsString(Type $type): bool
	{
		if ($type instanceof SafeStringType ||
			$type instanceof ConstantStringType) {
			return true;
		}

		if ($type->isLiteralString()->yes()) {
			return true;
		}
		if ($type->isNumericString()->yes()) {
			return true;
		}

		return false;
	}

	public static function accepts(Type $type): bool
	{
		if ($type instanceof ErrorType) {
			return true;
		}
		if ($type instanceof IntegerType ||
			$type instanceof BooleanType ||
			$type instanceof NullType) {
			return true;
		}

		if (self::acceptsString($type)) {
			return true;
		}

		if ($type instanceof ObjectType) {
			return true;
		}

		if ($type instanceof UnionType) {
			$innerTypes = $type->getTypes();
			foreach ($innerTypes as $innerType) {
				if ($innerType instanceof AccessoryType) {
					continue;
				}

				if (self::accepts($innerType)) {
					continue;
				}

				if ($innerType instanceof StringType) {
					return false;
				}
			}
			return true;
		}

		if ($type instanceof IntersectionType) {
			$innerTypes = $type->getTypes();
			foreach ($innerTypes as $innerType) {
				if ($innerType instanceof AccessoryType) {
					continue;
				}

				if (self::accepts($innerType)) {
					return true;
				}

				if ($innerType instanceof StringType) {
					continue;
				}
			}
			return false;
		}

		if ($type instanceof ArrayType) {
			return self::isSafeArray($type);
		}

		$stringType = $type->toString();
		if ($stringType instanceof StringType) {
			return self::acceptsString($stringType);
		}

		// unknown type is accepts
		return true;
	}

	public static function isSafeArray(ArrayType $type): bool
	{
		if ($type instanceof ConstantArrayType) {
			foreach ($type->getValueTypes() as $innerType) {
				if (!self::accepts($innerType)) {
					return false;
				}
			}
			return true;
		}

		return self::accepts($type->getItemType());
	}

	public static function isSafeUnionArray(UnionType $type): bool
	{
		foreach ($type->getTypes() as $innerType) {
			if ($innerType instanceof UnionType) {
				if (!self::isSafeUnionArray($innerType)) {
					return false;
				}
			} elseif ($innerType instanceof ArrayType) {
				if (!self::isSafeArray($innerType)) {
					return false;
				}
			} else {
				return false;
			}
		}
		return true;
	}

	public static function isSafeAllArgs(FuncCall $functionCall, Scope $scope): bool
	{
		$isSafe = true;
		$isConstantOnly = true;
		foreach ($functionCall->args as $arg) {
			$argType = $scope->getType($arg->value);
			if (!$argType instanceof ConstantScalarType) {
				$isConstantOnly = false;
			}

			if (!self::accepts($argType)) {
				$isSafe = false;
			}
		}

		// $isConstantOnly: true, $isSafe: true => transfer parent class (constant string?)
		// $isConstantOnly: true, $isSafe: false => nothing
		// $isConstantOnly: false, $isSafe: true => safe string
		// $isConstantOnly: false, $isSafe: false => transfer parent class (string?)
		return !$isConstantOnly && $isSafe;
	}

}
