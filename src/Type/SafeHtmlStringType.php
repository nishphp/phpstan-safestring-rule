<?php

namespace Nish\PHPStan\Type;

use PHPStan\Type\Type;

class SafeHtmlStringType extends SafeStringType
{
	/**
	 * @param mixed[] $properties
	 * @return Type
	 */
	public static function __set_state(array $properties): Type
	{
		return new self();
	}
}
