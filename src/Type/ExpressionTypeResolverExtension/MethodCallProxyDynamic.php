<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\ExpressionTypeResolverExtension;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;

/**
 * @implements MethodCallProxy<MethodCall,DynamicMethodReturnTypeExtension>
 */
class MethodCallProxyDynamic implements MethodCallProxy
{

	/** @var array<DynamicMethodReturnTypeExtension> $extensions */
	public array $extensions;

	/**
	 * @param array<DynamicMethodReturnTypeExtension> $extensions
	 */
	public function __construct(array $extensions)
	{
		$this->extensions = $extensions;
	}

	/**
	 * @param DynamicMethodReturnTypeExtension $extension
	 */
	public function isSupported($extension, MethodReflection $methodReflection): bool
	{
		return $extension->isMethodSupported($methodReflection);
	}

	/**
	 * @param DynamicMethodReturnTypeExtension $extension
	 * @param MethodCall $normalizedNode
	 */
	public function getTypeFromMethodCall($extension, MethodReflection $methodReflection, $normalizedNode, Scope $scope): ?Type
	{
		return $extension->getTypeFromMethodCall(
			$methodReflection,
			$normalizedNode,
			$scope,
		);
	}

}
