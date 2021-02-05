<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Analyser;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Type\Type;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\FunctionReflection;

class MutatingScope extends \PHPStan\Analyser\MutatingScope
{

	/**
	 * copy from \PHPStan\Analyser\MutatingScope::__construct
	 *
	 * @param \PHPStan\Analyser\ScopeFactory $scopeFactory
	 * @param \PHPStan\Reflection\ReflectionProvider $reflectionProvider
	 * @param \PHPStan\Type\DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry
	 * @param \PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry $operatorTypeSpecifyingExtensionRegistry
	 * @param \PhpParser\PrettyPrinter\Standard $printer
	 * @param \PHPStan\Analyser\TypeSpecifier $typeSpecifier
	 * @param \PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder
	 * @param \PHPStan\Parser\Parser $parser
	 * @param \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver
	 * @param \PHPStan\Analyser\ScopeContext $context
	 * @param bool $declareStrictTypes
	 * @param array<string, Type> $constantTypes
	 * @param \PHPStan\Reflection\FunctionReflection|MethodReflection|null $function
	 * @param string|null $namespace
	 * @param \PHPStan\Analyser\VariableTypeHolder[] $variablesTypes
	 * @param \PHPStan\Analyser\VariableTypeHolder[] $moreSpecificTypes
	 * @param array<string, \PHPStan\Analyser\ConditionalExpressionHolder[]> $conditionalExpressions
	 * @param string|null $inClosureBindScopeClass
	 * @param \PHPStan\Reflection\ParametersAcceptor|null $anonymousFunctionReflection
	 * @param bool $inFirstLevelStatement
	 * @param array<string, true> $currentlyAssignedExpressions
	 * @param array<string, Type> $nativeExpressionTypes
	 * @param array<MethodReflection|FunctionReflection> $inFunctionCallsStack
	 * @param string[] $dynamicConstantNames
	 * @param bool $treatPhpDocTypesAsCertain
	 * @param bool $afterExtractCall
	 * @param \PHPStan\Analyser\Scope|null $parentScope
	 */
	public function __construct(
		\PHPStan\Analyser\ScopeFactory $scopeFactory,
		\PHPStan\Reflection\ReflectionProvider $reflectionProvider,
		\PHPStan\Type\DynamicReturnTypeExtensionRegistry $dynamicReturnTypeExtensionRegistry,
		\PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry $operatorTypeSpecifyingExtensionRegistry,
		\PhpParser\PrettyPrinter\Standard $printer,
		\PHPStan\Analyser\TypeSpecifier $typeSpecifier,
		\PHPStan\Rules\Properties\PropertyReflectionFinder $propertyReflectionFinder,
		\PHPStan\Parser\Parser $parser,
		\PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver,
		\PHPStan\Analyser\ScopeContext $context,
		bool $declareStrictTypes = false,
		array $constantTypes = [],
		$function = null,
		?string $namespace = null,
		array $variablesTypes = [],
		array $moreSpecificTypes = [],
		array $conditionalExpressions = [],
		?string $inClosureBindScopeClass = null,
		?\PHPStan\Reflection\ParametersAcceptor $anonymousFunctionReflection = null,
		bool $inFirstLevelStatement = true,
		array $currentlyAssignedExpressions = [],
		array $nativeExpressionTypes = [],
		array $inFunctionCallsStack = [],
		array $dynamicConstantNames = [],
		bool $treatPhpDocTypesAsCertain = true,
		bool $afterExtractCall = false,
		?\PHPStan\Analyser\Scope $parentScope = null
	)
	{
		$replacedDynamicReturnTypeExtensionRegistry
			= new \Nish\PHPStan\Type\DynamicReturnTypeExtensionRegistry($dynamicReturnTypeExtensionRegistry);

		parent::__construct(
			$scopeFactory,
			$reflectionProvider,
			$replacedDynamicReturnTypeExtensionRegistry,
			$operatorTypeSpecifyingExtensionRegistry,
			$printer,
			$typeSpecifier,
			$propertyReflectionFinder,
			$parser,
			$nodeScopeResolver,
			$context,
			$declareStrictTypes,
			$constantTypes,
			$function,
			$namespace,
			$variablesTypes,
			$moreSpecificTypes,
			$conditionalExpressions,
			$inClosureBindScopeClass,
			$anonymousFunctionReflection,
			$inFirstLevelStatement,
			$currentlyAssignedExpressions,
			$nativeExpressionTypes,
			$inFunctionCallsStack,
			$dynamicConstantNames,
			$treatPhpDocTypesAsCertain,
			$afterExtractCall,
			$parentScope
		);
	}

	public function getType(Expr $node): Type
	{
		$parentResult = parent::getType($node);
		if (!RuleHelper::accepts($parentResult)) {
			$type = $this->resolveTypeExtension($node);

			if ($type !== null) {
				return $type;
			}
		}
		return $parentResult;
	}

	private function resolveTypeExtension(Expr $node): ?Type
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

		$leftStringType = $this->getType($left)->toString();
		$rightStringType = $this->getType($right)->toString();

		if (RuleHelper::accepts($leftStringType) && RuleHelper::accepts($rightStringType)) {
			return new SafeStringType();
		}

		return null;
	}

}
