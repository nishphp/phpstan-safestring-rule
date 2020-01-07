<?php

namespace Nish\PHPStan\Type;

use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\Type;

class SafeHtmlStringType extends SafeStringType
{
	public function describe(VerbosityLevel $level): string
	{
		return 'safehtml-string';
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
