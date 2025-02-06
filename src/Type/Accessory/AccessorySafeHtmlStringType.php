<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Accessory;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\VerbosityLevel;

class AccessorySafeHtmlStringType extends AccessorySafeStringType
{

	public function describe(VerbosityLevel $level): string
	{
		return 'safehtml-string';
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode('safehtml-string');
	}

}
