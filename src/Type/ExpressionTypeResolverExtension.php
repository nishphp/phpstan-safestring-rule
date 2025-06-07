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
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Container;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ExpressionTypeResolverExtension implements \PHPStan\Type\ExpressionTypeResolverExtension
{

	/** @var array<int,DynamicFunctionReturnTypeExtension> */
	private array $dynamicReturnTypeExtensions = [];

	/** @var array<int,DynamicMethodReturnTypeExtension> */
	private array $dynamicMethodReturnTypeExtensions = [];

	/** @var DynamicMethodReturnTypeExtension[][]|null */
	private ?array $dynamicMethodReturnTypeExtensionsByClass = null;

	/** @var array<int,DynamicStaticMethodReturnTypeExtension> */
	private array $dynamicStaticMethodReturnTypeExtensions = [];

	/** @var DynamicStaticMethodReturnTypeExtension[][]|null */
	private ?array $dynamicStaticMethodReturnTypeExtensionsByClass = null;

	private const DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG = 'nish.phpstan.broker.dynamicFunctionReturnTypeExtension';
	private const DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG = 'nish.phpstan.broker.dynamicMethodReturnTypeExtension';
	private const DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG = 'nish.phpstan.broker.dynamicStaticMethodReturnTypeExtension';

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

		/** @var array<int,DynamicMethodReturnTypeExtension> */
		$methodExtensions = $container->getServicesByTag(self::DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG);
		$this->dynamicMethodReturnTypeExtensions = $methodExtensions;

		/** @var array<int,DynamicStaticMethodReturnTypeExtension> */
		$staticMethodExtensions = $container->getServicesByTag(self::DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG);
		$this->dynamicStaticMethodReturnTypeExtensions = $staticMethodExtensions;
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

		$type = self::getTypeMethodCall($node, $scope);
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

	private function getTypeMethodCall(Expr $node, Scope $scope): ?Type
	{
		if (!($node instanceof MethodCall) && !($node instanceof StaticCall)) {
			return null;
		}

		$methodName = $node->name;
		if (!($methodName instanceof Node\Identifier)) {
			return null;
		}

		$methodNameString = $methodName->toString();

		// Get type with method
		if ($node instanceof MethodCall) {
			$typeWithMethod = $scope->getType($node->var);
		} else {
			// StaticCall
			if ($node->class instanceof Node\Name) {
				$className = $scope->resolveName($node->class);
				$typeWithMethod = new \PHPStan\Type\ObjectType($className);
			} else {
				$typeWithMethod = $scope->getType($node->class);
			}
		}

		$hasMethod = $typeWithMethod->hasMethod($methodNameString);
		if (!$hasMethod->yes()) {
			return null;
		}

		$methodReflection = $typeWithMethod->getMethod($methodNameString, $scope);

		// Select and normalize arguments
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$methodReflection->getVariants(),
			$methodReflection->getNamedArgumentsVariants(),
		);
		
		if ($node instanceof MethodCall) {
			$normalizedNode = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $node);
		} else {
			$normalizedNode = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $node);
		}
		
		if ($normalizedNode === null) {
			return null;
		}

		$resolvedTypes = [];
		foreach ($typeWithMethod->getObjectClassNames() as $className) {
			if ($normalizedNode instanceof MethodCall) {
				foreach ($this->getDynamicMethodReturnTypeExtensionsForClass($className) as $dynamicMethodReturnTypeExtension) {
					if (!$dynamicMethodReturnTypeExtension->isMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedType = $dynamicMethodReturnTypeExtension->getTypeFromMethodCall(
						$methodReflection,
						$normalizedNode,
						$scope,
					);
					if ($resolvedType === null) {
						continue;
					}

					$resolvedTypes[] = $resolvedType;
				}
			} else {
				foreach ($this->getDynamicStaticMethodReturnTypeExtensionsForClass($className) as $dynamicStaticMethodReturnTypeExtension) {
					if (!$dynamicStaticMethodReturnTypeExtension->isStaticMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedType = $dynamicStaticMethodReturnTypeExtension->getTypeFromStaticMethodCall(
						$methodReflection,
						$normalizedNode,
						$scope,
					);
					if ($resolvedType === null) {
						continue;
					}

					$resolvedTypes[] = $resolvedType;
				}
			}
		}

		if (count($resolvedTypes) > 0) {
			return TypeCombinator::union(...$resolvedTypes);
		}

		return null;
	}

	/**
	 * @return array<int, DynamicMethodReturnTypeExtension>
	 */
	private function getDynamicMethodReturnTypeExtensionsForClass(string $className): array
	{
		if ($this->dynamicMethodReturnTypeExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->dynamicMethodReturnTypeExtensions as $extension) {
				$byClass[strtolower($extension->getClass())][] = $extension;
			}

			$this->dynamicMethodReturnTypeExtensionsByClass = $byClass;
		}
		
		$extensions = $this->getDynamicExtensionsForType($this->dynamicMethodReturnTypeExtensionsByClass, $className);
		/** @var array<int, DynamicMethodReturnTypeExtension> $extensions */
		return $extensions;
	}

	/**
	 * @return array<int, DynamicStaticMethodReturnTypeExtension>
	 */
	private function getDynamicStaticMethodReturnTypeExtensionsForClass(string $className): array
	{
		if ($this->dynamicStaticMethodReturnTypeExtensionsByClass === null) {
			$byClass = [];
			foreach ($this->dynamicStaticMethodReturnTypeExtensions as $extension) {
				$byClass[strtolower($extension->getClass())][] = $extension;
			}

			$this->dynamicStaticMethodReturnTypeExtensionsByClass = $byClass;
		}
		
		$extensions = $this->getDynamicExtensionsForType($this->dynamicStaticMethodReturnTypeExtensionsByClass, $className);
		/** @var array<int, DynamicStaticMethodReturnTypeExtension> $extensions */
		return $extensions;
	}

	/**
	 * @param DynamicMethodReturnTypeExtension[][]|DynamicStaticMethodReturnTypeExtension[][] $extensions
	 * @return array<DynamicMethodReturnTypeExtension|DynamicStaticMethodReturnTypeExtension>
	 */
	private function getDynamicExtensionsForType(array $extensions, string $className): array
	{
		if (!$this->reflectionProvider->hasClass($className)) {
			return [];
		}

		$extensionsForClass = [[]];
		$class = $this->reflectionProvider->getClass($className);
		foreach (array_merge([$className], $class->getParentClassesNames(), $class->getNativeReflection()->getInterfaceNames()) as $extensionClassName) {
			$extensionClassName = strtolower($extensionClassName);
			if (!isset($extensions[$extensionClassName])) {
				continue;
			}

			$extensionsForClass[] = $extensions[$extensionClassName];
		}

		return array_merge(...$extensionsForClass);
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
