<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Return_>
 */
class SafeStringReturnTypeRule implements Rule
{

	/** @var array<string,string> funcs,methods */
	private $patterns = [];

	/** @param array<int,string> $patterns */
	public function __construct(array $patterns)
	{
		foreach ($patterns as $p) {
			$this->patterns[$p] = $p;
		}
	}

	public function getNodeType(): string
	{
		return Return_::class;
	}

	/** @return array<string|\PHPStan\Rules\RuleError> errors */
	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getFunction() === null) {
			return [];
		}

		if ($scope->isInAnonymousFunction()) {
			return [];
		}

		$function = $scope->getFunction();

		if ($function instanceof MethodReflection) {
			$name = sprintf(
				'%s::%s',
				$function->getDeclaringClass()->getDisplayName(),
				$function->getName()
			);
			$msg = sprintf('Method %s()', $name);

		} else {
			$name = $function->getName();
			$msg = sprintf('Function %s()', $name);
		}

		if (!isset($this->patterns[$name])) {
			return [];
		}

		assert($node instanceof Return_);

		$returnValue = $node->expr;
		if (!$returnValue) {
			return [];
		}
		$returnValueType = $scope->getType($returnValue);

		if (!RuleHelper::accepts($returnValueType)) {
			return [
				RuleErrorBuilder::message(sprintf(
					$msg . ' should return safe-string but returns %s.',
					$returnValueType->describe(VerbosityLevel::value())
				))->line($node->getLine())->build(),
			];
		}

		return [];
	}

}
