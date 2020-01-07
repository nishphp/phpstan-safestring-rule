<?php

namespace Nish\PHPStan\PhpDoc;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Type\Type;
use Nish\PHPStan\Type\SafeStringType;
use Nish\PHPStan\Type\SafeHtmlStringType;

class TypeNodeResolverExtension implements \PHPStan\PhpDoc\TypeNodeResolverExtension
{
	public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
	{
		if ($typeNode instanceof IdentifierTypeNode) {
            if ($typeNode->name === 'safehtml-string'){
                return new SafeHtmlStringType();
            }
            if ($typeNode->name === 'safe-string'){
                return new SafeStringType();
            }
        }

		return null;
    }
}
