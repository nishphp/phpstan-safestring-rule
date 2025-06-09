<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type\ExpressionTypeResolverExtension;

use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Type;

/**
 * @template T of StaticCall|MethodCall
 * @template U of DynamicMethodReturnTypeExtension|DynamicStaticMethodReturnTypeExtension
 */
interface MethodCallProxy
{

	/**
	 * @param array<U> $extensions
	 */
	public function __construct(array $extensions);

	/** @param U $extension */
	public function isSupported($extension, MethodReflection $methodReflection): bool;

	/**
	 * @param U $extension
	 * @param T $normalizedNode
	 */
	public function getTypeFromMethodCall($extension, MethodReflection $methodReflection, $normalizedNode, Scope $scope): ?Type;

}
