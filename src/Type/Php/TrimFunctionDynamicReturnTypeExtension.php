<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\Accessory\AccessorySafeStringType;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class TrimFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private \PHPStan\Type\Php\TrimFunctionDynamicReturnTypeExtension $parentClass,
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

		$originalResult = $this->parentClass->getTypeFromFunctionCall($functionReflection, $functionCall, $scope);
		if (!$originalResult) {
			return null;
		}

		if (RuleHelper::accepts($argType)) {
			return TypeCombinator::intersect($originalResult, new AccessorySafeStringType());
		}

		return $originalResult;
	}

}
