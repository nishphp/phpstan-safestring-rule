<?php

namespace Nish\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\IntegerType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\StringType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\VerbosityLevel;
use Nish\PHPStan\Type\SafeHtmlType;

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

	public function processNode(Node $node, Scope $scope): array
	{
		$messages = [];

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

            if ($type instanceof ErrorType)
                continue;
            if ($type instanceof IntegerType ||
                $type instanceof BooleanType ||
                $type instanceof ConstantStringType)
                continue;

            if ($type instanceof SafeHtmlType)
                continue;

            if ($type instanceof ObjectType){
                continue;
            }


            if ($type->toString() instanceof StringType){
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
