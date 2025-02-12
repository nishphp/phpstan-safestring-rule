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
use PHPStan\Type\Type;

class DynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension,
	DynamicMethodReturnTypeExtension, DynamicStaticMethodReturnTypeExtension
{

	private Type $type;

	/** @var string|array<string> */
	private string|array $func;

	/** @var ?class-string */
	private ?string $class = null;

	/**
	 * @param string|array<string> $func
	 */
	public function __construct(string|array $func, Type $type)
	{
		$this->type = $type;
		$this->parseArgs($func);
	}

	/** @param string|array<string> $func */
	private function parseArgs(string|array $func): void
	{
		if (is_array($func)) {
			$funcs = [];
			foreach ($func as $f) {
				$funcs[$f] = $f;
			}
			$this->func = $funcs;
			return;
		}

		$parts = explode('::', $func);
		if (isset($parts[1]) && (class_exists($parts[0]) || interface_exists($parts[0]))) {
			$this->class = $parts[0];
			$this->func = $parts[1];
		} else {
			$this->func = $func;
		}
	}

	/** @return class-string */
	public function getClass(): string
	{
		return $this->class ?: 'stdClass';
	}

	private function getTypeFromCall(FunctionReflection|MethodReflection $functionReflection, CallLike $functionCall, Scope $scope): Type
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
