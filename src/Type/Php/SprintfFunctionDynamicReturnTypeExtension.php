<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Type;

class SprintfFunctionDynamicReturnTypeExtension extends \PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension
{

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$originalResult = parent::getTypeFromFunctionCall($functionReflection, $functionCall, $scope);
		if (!RuleHelper::accepts($originalResult)) {
			if (RuleHelper::isSafeAllArgs($functionCall, $scope)) {
				return new SafeStringType();
			}
		}

		return $originalResult;
	}

}
