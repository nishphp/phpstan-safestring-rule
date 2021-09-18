<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Type;

class ImplodeFunctionDynamicReturnTypeExtension extends \PHPStan\Type\Php\ImplodeFunctionReturnTypeExtension
{

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$originalResult = parent::getTypeFromFunctionCall($functionReflection, $functionCall, $scope);
		if (RuleHelper::accepts($originalResult)) {
			return $originalResult;
		}

		$glueType = $scope->getType($functionCall->args[0]->value);
		$piecesType = $scope->getType($functionCall->args[1]->value);

		if (!RuleHelper::acceptsString($glueType)) {
			return $originalResult;
		}

		if (!RuleHelper::accepts($piecesType)) {
			return $originalResult;
		}

		return new SafeStringType();
	}

}
