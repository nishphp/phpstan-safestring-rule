<?php declare(strict_types = 1);

namespace Nish\PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Type\Type;
use PHPStan\Type\ErrorType;
use PHPStan\Type\TypeCombinator;
use Nish\PHPStan\Type\SafeStringType;
use Nish\PHPStan\Rules\RuleHelper;

class MutatingScope extends \PHPStan\Analyser\MutatingScope
{
	public function getType(Expr $node): Type
	{
        $type = $this->resolveTypeExtension($node);

        if ($type !== null)
            return $type;

        return parent::getType($node);
    }

    private function resolveTypeExtension(Expr $node): ?Type
    {
		if (!($node instanceof Expr\BinaryOp\Concat) &&
            !($node instanceof Expr\AssignOp\Concat))
            return null;

        if ($node instanceof Node\Expr\AssignOp) {
            $left = $node->var;
            $right = $node->expr;
        } else {
            $left = $node->left;
            $right = $node->right;
        }

        $leftStringType = $this->getType($left)->toString();
        $rightStringType = $this->getType($right)->toString();
        if (TypeCombinator::union(
            $leftStringType,
            $rightStringType
        ) instanceof ErrorType) {
            return new ErrorType();
        }

        if ($leftStringType instanceof SafeStringType && RuleHelper::accepts($rightStringType))
            return new SafeStringType();

        if ($rightStringType instanceof SafeStringType && RuleHelper::accepts($leftStringType))
            return new SafeStringType();

        return null;
    }
}