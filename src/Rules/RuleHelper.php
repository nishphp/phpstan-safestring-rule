<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use Nish\PHPStan\Type\Accessory\AccessorySafeStringType;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class RuleHelper
{

	public static function acceptsString(Type $type): bool
	{
		if (!$type->isString()->yes()) {
			return false;
		}

		if ($type instanceof AccessorySafeStringType ||
			count($type->getConstantStrings()) > 0) {
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

	public static function accepts(?Type $type): bool
	{
		if ($type === null) {
			return false;
		}

		if ($type instanceof ErrorType) {
			return true;
		}
		if ($type->isInteger()->yes() ||
			$type->isBoolean()->yes() ||
			$type->isNull()->yes()) {
			return true;
		}

		if (self::acceptsString($type)) {
			return true;
		}

		if ($type->isObject()->yes()) {
			return true;
		}

		if ($type instanceof UnionType) {
			$innerTypes = $type->getTypes();
			foreach ($innerTypes as $innerType) {
				if (self::accepts($innerType)) {
					continue;
				}

				if ($innerType->isString()->yes()) {
					return false;
				}
			}
			return true;
		}

		if ($type instanceof IntersectionType) {
			$innerTypes = $type->getTypes();
			foreach ($innerTypes as $innerType) {
				if (self::accepts($innerType)) {
					return true;
				}

				if ($innerType->isString()->yes()) {
					continue;
				}
			}
			return false;
		}

		if ($type->isArray()->yes()) {
			return self::isSafeArray($type);
		}

		$stringType = $type->toString();
		if ($stringType->isString()->yes()) {
			return self::acceptsString($stringType);
		}

		// unknown type is accepts
		return true;
	}

	public static function isSafeArray(Type $type): bool
	{
		if ($type instanceof ConstantArrayType) {
			foreach ($type->getValueTypes() as $innerType) {
				if (!self::accepts($innerType)) {
					return false;
				}
			}
			return true;
		}

		return self::accepts($type->getIterableValueType());
	}

	public static function isSafeUnionArray(UnionType $type): bool
	{
		foreach ($type->getTypes() as $innerType) {
			if ($innerType instanceof UnionType) {
				if (!self::isSafeUnionArray($innerType)) {
					return false;
				}
			} elseif ($innerType->isArray()->yes()) {
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
		foreach ($functionCall->getArgs() as $arg) {
			$argType = $scope->getType($arg->value);
			if (!self::accepts($argType)) {
				$isSafe = false;
			}
		}

		return $isSafe;
	}

}
