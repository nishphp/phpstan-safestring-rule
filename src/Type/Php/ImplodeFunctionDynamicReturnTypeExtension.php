<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Type;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;

class ImplodeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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
		$originalResult = $this->parentClass->getTypeFromFunctionCall($functionReflection, $functionCall, $scope);
		if (RuleHelper::accepts($originalResult)) {
			return $originalResult;
		}

		$args = $functionCall->getArgs();
		$glueType = $scope->getType($args[0]->value);
		$piecesType = $scope->getType($args[1]->value);

		if (!RuleHelper::acceptsString($glueType)) {
			return $originalResult;
		}

		if (!RuleHelper::accepts($piecesType)) {
			return $originalResult;
		}

		return new SafeStringType();
	}

}
