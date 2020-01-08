<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use Nish\PHPStan\Type\SafeStringType;

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
        $isSafe = true;
        $isConstantOnly = true;

		$glueType = $scope->getType($functionCall->args[0]->value);
		$piecesType = $scope->getType($functionCall->args[1]->value);

        if (!$glueType instanceof ConstantStringType &&
            !$glueType instanceof SafeStringType)
            return new StringType();

        if (!$piecesType instanceof ArrayType)
            return new StringType();

        $itemType = $piecesType->getItemType();
        if ($itemType instanceof ConstantStringType ||
            $itemType instanceof SafeStringType)
            return new SafeStringType();

        return new StringType();
	}

}
