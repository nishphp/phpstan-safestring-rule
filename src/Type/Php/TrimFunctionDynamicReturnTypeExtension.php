<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Nish\PHPStan\Type\SafeStringType;
use Nish\PHPStan\Rules\RuleHelper;

class TrimFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['trim', 'ltrim', 'rtrim']);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$argType = $scope->getType($functionCall->args[0]->value);
        if (RuleHelper::accepts($argType))
            return new SafeStringType();

        return new StringType();
	}
}
