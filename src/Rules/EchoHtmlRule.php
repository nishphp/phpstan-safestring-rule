<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

/**
 * @implements \PHPStan\Rules\Rule<\PhpParser\Node\Stmt\Echo_>
 */
class EchoHtmlRule implements Rule
{

	/** @var RuleLevelHelper */
	private $ruleLevelHelper;

	public function __construct(RuleLevelHelper $ruleLevelHelper)
	{
		$this->ruleLevelHelper = $ruleLevelHelper;
	}

	public function getNodeType(): string
	{
		return Node\Stmt\Echo_::class;
	}

	/** @return array<string|\PHPStan\Rules\RuleError> errors */
	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];

		assert($node instanceof Node\Stmt\Echo_);
		foreach ($node->exprs as $key => $expr) {
			$typeResult = $this->ruleLevelHelper->findTypeToCheck(
				$scope,
				$expr,
				'',
				static function (Type $type): bool {
					return !$type->toString() instanceof ErrorType;
				}
			);

			$type = $typeResult->getType();

			if (!RuleHelper::accepts($type)) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'Parameter #%d (%s) is not safehtml-string.',
					$key + 1,
					$type->describe(VerbosityLevel::value())
				))->line($expr->getLine())->build();
			}
		}
		return $messages;
	}

}
