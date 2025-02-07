<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use Nish\PHPStan\Type\Accessory\AccessorySafeHtmlStringType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\StringType;

class SafeHtmlStringReturnTypeExtension extends DynamicReturnTypeExtension
{

	/** @param string|array<string> $func */
	public function __construct(string|array $func)
	{
		parent::__construct($func, new IntersectionType([
			new StringType(),
			new AccessorySafeHtmlStringType(),
		]));
	}

}
