<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ParametersAcceptorSelector;
use Nish\PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension;
use Nish\PHPStan\Type\Php\ReplaceFunctionsDynamicReturnTypeExtension;
use Nish\PHPStan\Type\Php\ImplodeFunctionDynamicReturnTypeExtension;
use Nish\PHPStan\Type\Php\TrimFunctionDynamicReturnTypeExtension;

class ExpressionTypeResolverExtension implements \PHPStan\Type\ExpressionTypeResolverExtension
{
	private $dynamicReturnTypeExtensions = [];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private SprintfFunctionDynamicReturnTypeExtension $sprintfExtension,
        private ReplaceFunctionsDynamicReturnTypeExtension $replaceExtension,
        private ImplodeFunctionDynamicReturnTypeExtension $implodeExtension,
        private TrimFunctionDynamicReturnTypeExtension $trimExtension,
	)
	{
		$this->dynamicReturnTypeExtensions = [
            $sprintfExtension,
            $replaceExtension,
            $implodeExtension,
            $trimExtension,
        ];
	}

	public function getType(Expr $node, Scope $scope): ?Type
	{
		$type = self::getTypeConcat($node, $scope);
		if ($type)
				return $type;
	
		$type = self::getTypeFunction($node, $scope);
		if ($type)
			return $type;
	
		return null;
	}

	private function getTypeFunction(Expr $node, Scope $scope): ?Type
	{
		if (!($node instanceof FuncCall))
			return null;
		if ($node->name instanceof Expr)
			return null;

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
		if ($normalizedNode === null)
			return null;

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
		if ($node instanceof Expr\BinaryOp\Concat) {
			$parentResult = $this->initializerExprTypeResolver->getConcatType($node->left, $node->right, fn (Expr $expr): Type => $scope->getType($expr));
		}elseif ($node instanceof Expr\AssignOp\Concat) {
			$parentResult = $this->initializerExprTypeResolver->getConcatType($node->var, $node->expr, fn (Expr $expr): Type => $scope->getType($expr));
		}

		if ($parentResult === null)
			return null;

		if (!RuleHelper::accepts($parentResult)) {
			$type = $this->resolveTypeExtension($node, $scope);

			if ($type !== null) {
				return $type;
			}
		}
		return $parentResult;
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

		if (RuleHelper::accepts($leftStringType) && RuleHelper::accepts($rightStringType)) {
			return new SafeStringType();
		}

		return null;
	}

}
