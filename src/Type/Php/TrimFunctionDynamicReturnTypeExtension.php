<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

class TrimFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private \PHPStan\Type\Php\ImplodeFunctionReturnTypeExtension $parentClass,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $this->parentClass->isFunctionSupported($functionReflection);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): ?Type
	{
		$args = $functionCall->getArgs();
		$argType = $scope->getType($args[0]->value);
		if (RuleHelper::accepts($argType)) {
			return new SafeStringType();
		}

		return $this->parentClass->getTypeFromFunctionCall($functionReflection, $functionCall, $scope);
	}

}
