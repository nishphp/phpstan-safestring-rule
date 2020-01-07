<?php declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Type;
use PHPStan\Type\StringType;

class DynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension,
    DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension
{
    /** @var Type */
    private $type;

	/** @var string|array<string> */
	private $func;

    /** @var ?string */
    private $class;

    /** @param string|array<string> $func */
	public function __construct(string $type, $func)
	{
        $this->type = new $type;
        $this->parseArgs($func);
	}

    /** @param string|array<string> $func */
    private function parseArgs($func)
    {
        if (is_array($func)){
            $this->func = $func;
            return;
        }


        $parts = explode('::', $func);
        if (isset($parts[1])){
            $this->class = $parts[0];
            $this->func = $parts[1];
        }else{
            $this->func = $func;
        }

    }

	public function getClass(): string
    {
        return $this->class ?: 'stdClass';
    }

	/**
	 * @param \PHPStan\Reflection\ParametersAcceptor[] $params
	 */
    private function getTypeFromCall(array $params): Type
    {
		$returnType = ParametersAcceptorSelector::selectSingle($params)->getReturnType();
        if (!$returnType instanceof StringType)
            return $returnType;

        return $this->type;
    }

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        if ($this->class !== null)
            return false;

        $name = $functionReflection->getName();
        if (!is_array($this->func)){
            return $name === $this->func;
        }

        foreach ($this->func as $func){
            if ($name === $func)
                return true;
        }

        return false;
    }

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
    {
        return $this->getTypeFromCall($functionReflection->getVariants());
    }

	public function isMethodSupported(MethodReflection $methodReflection): bool
    {
		return $this->class !== null && (
            $this->func === '*' || $methodReflection->getName() === $this->func);
    }

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        return $this->getTypeFromCall($methodReflection->getVariants());
    }

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $this->isMethodSupported($methodReflection);
    }

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
    {
        return $this->getTypeFromCall($methodReflection->getVariants());
    }
}
