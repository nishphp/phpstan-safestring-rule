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

class SprintfFunctionDynamicReturnTypeExtension extends \PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension
{
	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'sprintf';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope
	): Type
	{
		$values = [];
        $isSafe = true;
        $isConstantOnly = true;
		foreach ($functionCall->args as $arg) {
			$argType = $scope->getType($arg->value);
			if ($argType instanceof ConstantScalarType) {
                $values[] = $argType->getValue();
			}else{
				$isConstantOnly = false;
            }

            if (!RuleHelper::accepts($argType))
                $isSafe = false;
		}

        if (!$isConstantOnly){
            if ($isSafe)
                return new SafeStringType();
            else
                return new StringType();
        }

		try {
			$value = @sprintf(...$values);
		} catch (\Throwable $e) {
			return new StringType();
		}

		return $scope->getTypeFromValue($value);
	}
}
