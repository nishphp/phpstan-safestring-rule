<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\Php\ImplodeFunctionDynamicReturnTypeExtension;
use Nish\PHPStan\Type\Php\ReplaceFunctionsDynamicReturnTypeExtension;
use Nish\PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension;
use Nish\PHPStan\Type\Php\TrimFunctionDynamicReturnTypeExtension;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ExpressionTypeResolverExtension implements \PHPStan\Type\ExpressionTypeResolverExtension
{

	/** @var array<int,DynamicFunctionReturnTypeExtension> */
	private array $dynamicReturnTypeExtensions = [];

	private const DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG = 'nish.phpstan.broker.dynamicFunctionReturnTypeExtension';

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		Container $container,
		SprintfFunctionDynamicReturnTypeExtension $sprintfExtension,
		ReplaceFunctionsDynamicReturnTypeExtension $replaceExtension,
		ImplodeFunctionDynamicReturnTypeExtension $implodeExtension,
		TrimFunctionDynamicReturnTypeExtension $trimExtension,
	)
	{
		$this->dynamicReturnTypeExtensions = [
			$sprintfExtension,
			$replaceExtension,
			$implodeExtension,
			$trimExtension,
		];

		/** @var array<int,DynamicFunctionReturnTypeExtension> */
		$extensions = $container->getServicesByTag(self::DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG);
		$this->dynamicReturnTypeExtensions = array_merge($this->dynamicReturnTypeExtensions, $extensions);
	}

	public function getType(Expr $node, Scope $scope): ?Type
	{
		$type = self::getTypeConcat($node, $scope);
		if ($type) {
				return $type;
		}

		$type = self::getTypeFunction($node, $scope);
		if ($type) {
			return $type;
		}

		return null;
	}

	private function getTypeFunction(Expr $node, Scope $scope): ?Type
	{
		if (!($node instanceof FuncCall)) {
			return null;
		}
		if ($node->name instanceof Expr) {
			return null;
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return null;
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);
		$normalizedNode = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
		if ($normalizedNode === null) {
			return null;
		}

		foreach ($this->dynamicReturnTypeExtensions as $dynamicFunctionReturnTypeExtension) {
			if (!$dynamicFunctionReturnTypeExtension->isFunctionSupported($functionReflection)) {
				continue;
			}

			$resolvedType = $dynamicFunctionReturnTypeExtension->getTypeFromFunctionCall(
				$functionReflection,
				$normalizedNode,
				$scope,
			);
			if ($resolvedType !== null) {
				return $resolvedType;
			}
		}

		return null;
	}

	private function getTypeConcat(Expr $node, Scope $scope): ?Type
	{
		$parentResult = null;
		if ($node instanceof Expr\BinaryOp\Concat || $node instanceof Expr\AssignOp\Concat) {
			$parentResult = $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($scope));
		}

		if ($parentResult === null) {
			return null;
		}

		if ($parentResult instanceof ErrorType) {
			return $parentResult;
		}

		if (!RuleHelper::accepts($parentResult)) {
			$type = $this->resolveTypeExtension($node, $scope);

			if ($type !== null) {
				return $type;
			}
		}
		return null;
	}

	private function resolveTypeExtension(Expr $node, Scope $scope): ?Type
	{
		if (!($node instanceof Expr\BinaryOp\Concat) &&
			!($node instanceof Expr\AssignOp\Concat)) {
			return null;
		}

		if ($node instanceof Node\Expr\AssignOp) {
			$left = $node->var;
			$right = $node->expr;
		} else {
			$left = $node->left;
			$right = $node->right;
		}

		$leftStringType = $scope->getType($left)->toString();
		$rightStringType = $scope->getType($right)->toString();
		if (TypeCombinator::union(
			$leftStringType,
			$rightStringType,
		) instanceof ErrorType) {
			return new ErrorType();
		}

		// @see phpstan-src: src/Rules/Operators/InvalidBinaryOperationRule.php
		// @see phpstan-src: src/Analyzer/MutatingScope.php
		// @see phpstan-src: src/Reflection/InitializerExprTypeResolver.php
		// var_dump($leftStringType->describe(\PHPStan\Type\VerbosityLevel::precise()));
		// var_dump($rightStringType->describe(\PHPStan\Type\VerbosityLevel::precise()));

		if (RuleHelper::acceptsString($leftStringType) && RuleHelper::acceptsString($rightStringType)) {
			return TypeCombinator::intersect(new StringType(), new Accessory\AccessorySafeStringType());
		}

		return null;
	}

}
