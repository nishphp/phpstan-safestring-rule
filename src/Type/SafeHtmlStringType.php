<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PHPStan\Type\VerbosityLevel;

class SafeHtmlStringType extends SafeStringType
{

	public function describe(VerbosityLevel $level): string
	{
		return 'safehtml-string';
	}

}
