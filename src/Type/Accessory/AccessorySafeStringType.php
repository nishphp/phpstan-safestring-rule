<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Accessory;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Type;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StringType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\UnionType;
use PHPStan\Type\TypeCombinator;

class AccessorySafeStringType implements CompoundType, AccessoryType
{
	use NonArrayTypeTrait;
	use NonCallableTypeTrait;
	use NonObjectTypeTrait;
	use NonIterableTypeTrait;
	use UndecidedBooleanTypeTrait;
	use UndecidedComparisonCompoundTypeTrait;
	use NonGenericTypeTrait;
	use NonRemoveableTypeTrait;


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

		if ($type instanceof CompoundType) {
			return $type->isAcceptedBy($this, $strictTypes);
		}

		return AcceptsResult::createNo();
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($this->equals($type)) {
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

	public function getReferencedClasses(): array
	{
		return [];
	}

	public function getObjectClassNames(): array
	{
		return [];
	}

	public function getObjectClassReflections(): array
	{
		return [];
	}

	public function getConstantStrings(): array
	{
		return [];
	}

	public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
	{
		if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
			return $otherType->isSuperTypeOf($this);
		}

		if ($this->equals($otherType)) {
			return IsSuperTypeOfResult::createYes();
		}

		return IsSuperTypeOfResult::createNo();
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self;
	}

	public function isOffsetAccessible(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isOffsetAccessLegal(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function hasOffsetValueType(Type $offsetType): TrinaryLogic
	{
		return $offsetType->isInteger()->and(TrinaryLogic::createMaybe());
	}

	public function getOffsetValueType(Type $offsetType): Type
	{
		if ($this->hasOffsetValueType($offsetType)->no()) {
			return new ErrorType();
		}

        return TypeCombinator::intersect(new StringType(), new AccessorySafeStringType());
	}

	public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = true): Type
	{
		$stringOffset = (new StringType())->setOffsetValueType($offsetType, $valueType, $unionValues);

		if ($stringOffset instanceof ErrorType) {
			return $stringOffset;
		}

		if ($this->accepts($valueType, true)->yes()) {
            return TypeCombinator::intersect($valueType, new AccessorySafeStringType());
		}

		return new StringType();
	}

	public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
	{
		return $this;
	}

	public function unsetOffset(Type $offsetType): Type
	{
		return new ErrorType();
	}

	public function toNumber(): Type
	{
		return new ErrorType();
	}

	public function toAbsoluteNumber(): Type
	{
		return new ErrorType();
	}

	public function toInteger(): Type
	{
		return new IntegerType();
	}

	public function toFloat(): Type
	{
		return new FloatType();
	}

	public function toString(): Type
	{
		return $this;
	}

	public function toArray(): Type
	{
		return new ConstantArrayType(
			[new ConstantIntegerType(0)],
			[$this],
			[1],
			[],
			TrinaryLogic::createYes(),
		);
	}

	public function toArrayKey(): Type
	{
		return $this;
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		return $this;
	}

	public function isNull(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isConstantValue(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isConstantScalarValue(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getConstantScalarTypes(): array
	{
		return [];
	}

	public function getConstantScalarValues(): array
	{
		return [];
	}

	public function isTrue(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFalse(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isBoolean(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isFloat(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInteger(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isNumericString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isNonEmptyString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isNonFalsyString(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isLiteralString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isLowercaseString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isClassString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function isUppercaseString(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	public function getClassStringObjectType(): Type
	{
		return new ObjectWithoutClassType();
	}

	public function getObjectTypeOrClassStringObjectType(): Type
	{
		return new ObjectWithoutClassType();
	}

	public function isVoid(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isScalar(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
	{
		if ($type->isNull()->yes()) {
			return new ConstantBooleanType(false);
		}

		return new BooleanType();
	}

	public function traverse(callable $cb): Type
	{
		return $this;
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		return $this;
	}

	public function generalize(GeneralizePrecision $precision): Type
	{
		return new StringType();
	}

	public function exponentiate(Type $exponent): Type
	{
		return new BenevolentUnionType([
			new FloatType(),
			new IntegerType(),
		]);
	}

	public function getFiniteTypes(): array
	{
		return [];
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('safe-string');
	}

}
