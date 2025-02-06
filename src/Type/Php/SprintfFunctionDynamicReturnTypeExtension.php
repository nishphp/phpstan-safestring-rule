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

class SprintfFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(
		private \PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension $parentClass,
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
		$originalResult = $this->parentClass->getTypeFromFunctionCall($functionReflection, $functionCall, $scope);
		if (!$originalResult) {
			return null;
		}

		if (!RuleHelper::accepts($originalResult)) {
			if (RuleHelper::isSafeAllArgs($functionCall, $scope)) {
				return TypeCombinator::intersect($originalResult, new AccessorySafeStringType());
			}
		}

		return $originalResult;
	}

}
