<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

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
