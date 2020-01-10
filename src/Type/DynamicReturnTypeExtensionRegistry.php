<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Type;

class DynamicReturnTypeExtensionRegistry extends \PHPStan\Type\DynamicReturnTypeExtensionRegistry
{

	/** @var \PHPStan\Type\DynamicReturnTypeExtensionRegistry */
	private $parent;

	public function __construct(\PHPStan\Type\DynamicReturnTypeExtensionRegistry $parent)
	{
		$this->parent = $parent;
	}

	public function getDynamicMethodReturnTypeExtensionsForClass(string $className): array
	{
		return $this->parent->getDynamicMethodReturnTypeExtensionsForClass($className);
	}
	public function getDynamicStaticMethodReturnTypeExtensionsForClass(string $className): array
	{
		return $this->parent->getDynamicStaticMethodReturnTypeExtensionsForClass($className);
	}

	/**
	 * @override
	 * @return \PHPStan\Type\DynamicFunctionReturnTypeExtension[]
	 */
	public function getDynamicFunctionReturnTypeExtensions(): array
	{
		static $extensions;
		if ($extensions === null) {
			// replace default extension
			$extensions = [
				new \Nish\PHPStan\Type\Php\SprintfFunctionDynamicReturnTypeExtension(),
				new \Nish\PHPStan\Type\Php\ReplaceFunctionsDynamicReturnTypeExtension(),
			];
		}
		return array_merge($extensions, $this->parent->getDynamicFunctionReturnTypeExtensions());
	}

}
