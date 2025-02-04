<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\ShouldNotHappenException;

class SafeStringType extends StringType
{

	// @see \PHPStan\Type\Traits\NonCallableTypeTrait;
	public function isCallable(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		throw new ShouldNotHappenException();
	}


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
			return  AcceptsResult::createYes();
		}

		if ($type->isLiteralString()->yes()) {
			return AcceptsResult::createYes();
		}

		if ($type->isNumericString()->yes()) {
			return AcceptsResult::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return AcceptsResult::createNo();
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof self) {
			return IsSuperTypeOfResult::createYes();
		}

		if (count($type->getConstantStrings()) !== 0) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($type->isLiteralString()->yes()) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($type->isNumericString()->yes()) {
			return IsSuperTypeOfResult::createYes();
		}

		if ($type instanceof CompoundType) {
			return $type->isSubTypeOf($this);
		}

		return IsSuperTypeOfResult::createNo();
	}
}
