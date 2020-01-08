<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type;

class SafeHtmlStringReturnTypeExtension extends DynamicReturnTypeExtension
{
    /** @param string|array<string> $func */
	public function __construct($func)
	{
        parent::__construct($func, SafeHtmlStringType::class);
	}
}
