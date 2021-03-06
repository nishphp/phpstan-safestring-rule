<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class ImplodeFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'implode';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$glueType = $scope->getType($functionCall->args[0]->value);
		$piecesType = $scope->getType($functionCall->args[1]->value);

		if (!$glueType instanceof ConstantStringType &&
			!$glueType instanceof SafeStringType) {
			return new StringType();
		}

		if ($piecesType instanceof UnionType) {
			if (RuleHelper::isSafeUnionArray($piecesType)) {
				return new SafeStringType();
			} else {
				return new StringType();
			}
		}

		if (!$piecesType instanceof ArrayType) {

			$toArray = $piecesType->toArray();
		} else {
			$toArray = $piecesType;
		}

		if (!$toArray instanceof ArrayType) {
			return new StringType();
		}

		if (RuleHelper::isSafeArray($toArray)) {
			return new SafeStringType();
		}

		return new StringType();
	}

}
