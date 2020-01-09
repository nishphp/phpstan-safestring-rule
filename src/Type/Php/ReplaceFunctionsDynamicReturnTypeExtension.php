<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Broker\BrokerFactory;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use Nish\PHPStan\Type\SafeStringType;
use Nish\PHPStan\Rules\RuleHelper;

class ReplaceFunctionsDynamicReturnTypeExtension extends \PHPStan\Type\Php\ReplaceFunctionsDynamicReturnTypeExtension
{
	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
        if (RuleHelper::isSafeAllArgs($functionCall, $scope))
            return new SafeStringType();

        return parent::getTypeFromFunctionCall($functionReflection, $functionCall, $scope);
	}
}
