<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\ExpressionTypeResolverExtension;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

/**
 * @implements MethodCallProxy<StaticCall,DynamicStaticMethodReturnTypeExtension>
 */
class MethodCallProxyStatic implements MethodCallProxy
{

	/** @var array<DynamicStaticMethodReturnTypeExtension> $extensions */
	public array $extensions;

	/**
	 * @param array<DynamicStaticMethodReturnTypeExtension> $extensions
	 */
	public function __construct(array $extensions)
	{
		$this->extensions = $extensions;
	}

	/**
	 * @param DynamicStaticMethodReturnTypeExtension $extension
	 */
	public function isSupported($extension, MethodReflection $methodReflection): bool
	{
		return $extension->isStaticMethodSupported($methodReflection);
	}

	/**
	 * @param DynamicStaticMethodReturnTypeExtension $extension
	 * @param StaticCall $normalizedNode
	 */
	public function getTypeFromMethodCall($extension, MethodReflection $methodReflection, $normalizedNode, Scope $scope): ?Type
	{
		return $extension->getTypeFromStaticMethodCall(
			$methodReflection,
			$normalizedNode,
			$scope,
		);
	}

}
