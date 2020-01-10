<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node>
 */
class SafeStringCallRule implements Rule
{

	/** @var \PHPStan\Rules\RuleLevelHelper */
	private $ruleLevelHelper;

	/** @var array<string, int> func => index of argument */
	private $patterns;

	/**
	 * @param array<string, int> $patterns
	 */
	public function __construct(array $patterns, RuleLevelHelper $ruleLevelHelper)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
		$this->patterns = $patterns;
	}

	public function getNodeType(): string
	{
		return Node::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node instanceof Expr\FuncCall &&
			!$node instanceof Expr\MethodCall &&
			!$node instanceof Expr\StaticCall &&
			!$node instanceof Expr\New_) {
			return [];
		}

		if ($node instanceof Expr\MethodCall) {
			if (!$node->name instanceof Node\Identifier) {
				return [];
			}

			$name = $node->name->name;
			$type = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$node->var,
				'',
				static function (Type $type) use ($name): bool {
					return $type->canCallMethods()->yes() && $type->hasMethod($name)->yes();
				}
			)->getType();

			if (!$type instanceof ObjectType || !$type->canCallMethods()->yes() || !$type->hasMethod($name)->yes()) {
				return [];
			}

			$func = $type->getClassName() . '::'
				  . $node->name->toString();

		} elseif ($node instanceof Expr\StaticCall) {
			if (!$node->class instanceof Node\Name ||
				!$node->name instanceof Node\Identifier) {
				return [];
			}

			$func = $node->class->toString() . '::'
				  . $node->name->toString();

		} elseif ($node instanceof Expr\New_) {
			if (!$node->class instanceof Node\Name) {
				return [];
			}

			$func = $node->class->toString() . '::'
					   . '__construct';

		} else {
			if (!$node->name instanceof Node\Name) {
				return [];
			}

			$func = $node->name->toString();
		}

		if (!isset($this->patterns[$func])) {
			return [];
		}
		$index = $this->patterns[$func];

		if (!isset($node->args[$index])) {
			return [];
		}
		$arg = $node->args[$index];

		$type = $this->ruleLevelHelper->findTypeToCheck(
			$scope,
			$arg->value,
			'',
			static function (Type $type): bool {
				return $type instanceof StringType;
			}
		)->getType();

		if (!RuleHelper::accepts($type)) {
			return [
				RuleErrorBuilder::message(sprintf(
					'Parameter #%d (%s) is not safe-string.',
					$index + 1,
					$type->describe(VerbosityLevel::value())
				))->line($node->getLine())->build(),
			];
		}

		return [];
	}

}
