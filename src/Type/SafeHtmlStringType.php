<?php

namespace Nish\PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\Type;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\StringType;

class SafeHtmlStringType extends SafeStringType
{
	public function describe(VerbosityLevel $level): string
	{
		return 'safehtml-string';
	}

	public function accepts(Type $type, bool $strictTypes): TrinaryLogic
	{
		if ($type instanceof StringType) {
			return TrinaryLogic::createYes();
		}

        return parent::accepts($type, $strictTypes);
    }

	public function isSuperTypeOf(Type $type): TrinaryLogic
	{
		if ($type instanceof self) {
			return TrinaryLogic::createYes();
		}

		if ($type instanceof ClassStringType) {
			return TrinaryLogic::createYes();
		}

        return parent::isSuperTypeOf($type);
	}



	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self();
	}
}
