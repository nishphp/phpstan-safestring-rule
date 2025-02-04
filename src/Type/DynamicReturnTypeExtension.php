<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;

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
	public function __construct($func, string $type = SafeStringType::class)
	{
		$this->type = new $type();
		$this->parseArgs($func);
	}

	/** @param string|array<string> $func */
	private function parseArgs($func): void
	{
		if (is_array($func)) {
			$this->func = [];
			foreach ($func as $f) {
				$this->func[$f] = $f;
			}
			return;
		}

		$parts = explode('::', $func);
		if (isset($parts[1])) {
			$this->class = $parts[0];
			$this->func = $parts[1];
		} else {
			$this->func = $func;
		}
	}

	public function getClass(): string
	{
		return $this->class ?: 'stdClass';
	}

	/**
     * @param FunctionReflection|MethodReflection $functionReflection
	 * @param \PHPStan\Reflection\ParametersAcceptor[] $params
	 */
	private function getTypeFromCall($functionReflection, CallLike $functionCall, Scope $scope): Type
	{
		$returnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$functionCall->getArgs(),
			$functionReflection->getVariants()
		)->getReturnType();

		if (!$returnType->isString()->yes()) {
			return $returnType;
		}

		return $this->type;
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		if ($this->class !== null) {
			return false;
		}

		$name = $functionReflection->getName();
		if (!is_array($this->func)) {
			return $name === $this->func;
		}

		return isset($this->func[$name]);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		return $this->getTypeFromCall($functionReflection, $functionCall, $scope);
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $this->class !== null && (
			$this->func === '*' || $methodReflection->getName() === $this->func);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		return $this->getTypeFromCall($methodReflection, $methodCall, $scope);
	}

	public function isStaticMethodSupported(MethodReflection $methodReflection): bool
	{
		return $this->isMethodSupported($methodReflection);
	}

	public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): Type
	{
		return $this->getTypeFromCall($methodReflection, $methodCall, $scope);
	}

}
