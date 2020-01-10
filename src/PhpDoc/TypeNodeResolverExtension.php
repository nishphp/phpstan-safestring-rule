<?php

declare(strict_types = 1);

namespace Nish\PHPStan\PhpDoc;

use Nish\PHPStan\Type\SafeHtmlStringType;
use Nish\PHPStan\Type\SafeStringType;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;

class TypeNodeResolverExtension implements \PHPStan\PhpDoc\TypeNodeResolverExtension
{

	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
	{
		if ($typeNode instanceof IdentifierTypeNode) {
			if ($typeNode->name === 'safehtml-string') {
				return new SafeHtmlStringType();
			}
			if ($typeNode->name === 'safe-string') {
				return new SafeStringType();
			}
		}

		return null;
	}

}
