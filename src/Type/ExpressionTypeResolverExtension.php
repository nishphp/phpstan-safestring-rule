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
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
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

	/** @var array<string, bool> */
	private array $resolvingNodes = [];

	/** @var array<string, array<string, true>> */
	private array $coreExtensionCache = [];

	private ?\PHPStan\Type\DynamicReturnTypeExtensionRegistry $coreRegistry = null;

	private const DYNAMIC_FUNCTION_RETURN_TYPE_EXTENSION_TAG = 'nish.phpstan.broker.dynamicFunctionReturnTypeExtension';
	private const DYNAMIC_METHOD_RETURN_TYPE_EXTENSION_TAG = 'nish.phpstan.broker.dynamicMethodReturnTypeExtension';
	private const DYNAMIC_STATIC_METHOD_RETURN_TYPE_EXTENSION_TAG = 'nish.phpstan.broker.dynamicStaticMethodReturnTypeExtension';

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private DynamicReturnTypeExtensionRegistryProvider $coreRegistryProvider,
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
		// Process with our extensions
		$type = self::getTypeConcat($node, $scope);
		if ($type !== null) {
			return $type;
		}

		$type = self::getTypeFunction($node, $scope);
		if ($type !== null) {
			return $type;
		}

		return self::getTypeMethodCall($node, $scope);
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

			// Check if PHPStan core has type for this function
			$coreType = $this->getCoreTypeForFunction($functionReflection, $normalizedNode, $scope);
			if ($coreType !== null && RuleHelper::accepts($coreType)) {
				return $coreType;
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
			if ($normalizedNode instanceof StaticCall) {
				$methodCallProxy = new ExpressionTypeResolverExtension\MethodCallProxyStatic(
					$this->getDynamicStaticMethodReturnTypeExtensionsForClass($className)
				);
			} else {
				$methodCallProxy = new ExpressionTypeResolverExtension\MethodCallProxyDynamic(
					$this->getDynamicMethodReturnTypeExtensionsForClass($className),
				);
			}

			foreach ($methodCallProxy->extensions as $extension) {
				if (!$methodCallProxy->isSupported($extension, $methodReflection)) {
					continue;
				}

				// Check if PHPStan core has type for this method
				$coreType = $this->getCoreTypeForMethodCall($className, $methodReflection, $normalizedNode, $scope);
				if ($coreType !== null && RuleHelper::accepts($coreType)) {
					return $coreType;
				}

				$resolvedType = $methodCallProxy->getTypeFromMethodCall($extension, $methodReflection, $normalizedNode, $scope);

				if ($resolvedType !== null) {
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
	 * @return array<DynamicMethodReturnTypeExtension>
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

		return $this->getDynamicExtensionsForType($this->dynamicMethodReturnTypeExtensionsByClass, $className);
	}

	/**
	 * @return array<DynamicStaticMethodReturnTypeExtension>
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

		return $this->getDynamicExtensionsForType($this->dynamicStaticMethodReturnTypeExtensionsByClass, $className);
	}

	/**
	 * @template T of DynamicMethodReturnTypeExtension|DynamicStaticMethodReturnTypeExtension
	 * @param T[][] $extensions
	 * @return array<T>
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
		if (!($node instanceof Expr\BinaryOp\Concat) && !($node instanceof Expr\AssignOp\Concat)) {
			return null;
		}

		$parentResult = $this->initializerExprTypeResolver->getType($node, InitializerExprContext::fromScope($scope));

		if ($parentResult instanceof ErrorType) {
			return $parentResult;
		}

		if (RuleHelper::accepts($parentResult)) {
			return null;
		}

		return $this->resolveTypeExtension($node, $scope);
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

	private function getCoreRegistry(): \PHPStan\Type\DynamicReturnTypeExtensionRegistry
	{
		if ($this->coreRegistry === null) {
			$this->coreRegistry = $this->coreRegistryProvider->getRegistry();
		}
		return $this->coreRegistry;
	}

	private function getCoreTypeForMethodCall(string $className, \PHPStan\Reflection\MethodReflection $methodReflection, MethodCall|StaticCall $call, Scope $scope): ?Type
	{
		$cacheKey = $className . '::' . $methodReflection->getName() . ($call instanceof StaticCall ? '::static' : '');

		if ($call instanceof StaticCall) {
			$methodCallProxy = new ExpressionTypeResolverExtension\MethodCallProxyStatic(
				$this->getCoreRegistry()->getDynamicStaticMethodReturnTypeExtensionsForClass($className),
			);
		} else {
			$methodCallProxy = new ExpressionTypeResolverExtension\MethodCallProxyDynamic(
				$this->getCoreRegistry()->getDynamicMethodReturnTypeExtensionsForClass($className),
			);
		}

		// Check cache first
		if (!isset($this->coreExtensionCache[$cacheKey])) {
			// Initialize cache entry
			$this->coreExtensionCache[$cacheKey] = [];

			// Get core extensions for this class
			foreach ($methodCallProxy->extensions as $extension) {
				if ($methodCallProxy->isSupported($extension, $methodReflection)) {
					$this->coreExtensionCache[$cacheKey][spl_object_hash($extension)] = true;
				}
			}
		}

		// If no core extensions support this method, return null
		if (!$this->coreExtensionCache[$cacheKey]) {
			return null;
		}

		// Get type from core extensions
		$nodeKey = spl_object_hash($call);
		if (isset($this->resolvingNodes[$nodeKey])) {
			return null;
		}

		$this->resolvingNodes[$nodeKey] = true;
		try {
			foreach ($methodCallProxy->extensions as $extension) {
				$extensionKey = spl_object_hash($extension);
				if (isset($this->coreExtensionCache[$cacheKey][$extensionKey])) {
					$type = $methodCallProxy->getTypeFromMethodCall($extension, $methodReflection, $call, $scope);
					if ($type !== null) {
						return $type;
					}
				}
			}
		} finally {
			unset($this->resolvingNodes[$nodeKey]);
		}

		return null;
	}


	private function getCoreTypeForFunction(\PHPStan\Reflection\FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		$functionName = $functionReflection->getName();
		$cacheKey = 'function::' . $functionName;

		// Check cache first
		if (!isset($this->coreExtensionCache[$cacheKey])) {
			// Initialize cache entry
			$this->coreExtensionCache[$cacheKey] = [];

			// Get core function extensions
			$coreFunctionExtensions = $this->getCoreRegistry()->getDynamicFunctionReturnTypeExtensions();

			// Check which core extensions support this function
			foreach ($coreFunctionExtensions as $extension) {
				if ($extension->isFunctionSupported($functionReflection)) {
					$this->coreExtensionCache[$cacheKey][spl_object_hash($extension)] = true;
				}
			}
		}

		// If no core extensions support this function, return null
		if (!$this->coreExtensionCache[$cacheKey]) {
			return null;
		}

		// Get type from core extensions
		$nodeKey = spl_object_hash($funcCall);
		if (isset($this->resolvingNodes[$nodeKey])) {
			return null;
		}

		$this->resolvingNodes[$nodeKey] = true;
		try {
			$coreFunctionExtensions = $this->getCoreRegistry()->getDynamicFunctionReturnTypeExtensions();
			foreach ($coreFunctionExtensions as $extension) {
				$extensionKey = spl_object_hash($extension);
				if (isset($this->coreExtensionCache[$cacheKey][$extensionKey])) {
					$type = $extension->getTypeFromFunctionCall($functionReflection, $funcCall, $scope);
					if ($type !== null) {
						return $type;
					}
				}
			}
		} finally {
			unset($this->resolvingNodes[$nodeKey]);
		}

		return null;
	}

}
