<?php

declare(strict_types = 1);

namespace Nish\PHPStan\Analyser;

use Nish\PHPStan\Rules\RuleHelper;
use Nish\PHPStan\Type\SafeStringType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Type\Type;

class MutatingScope extends \PHPStan\Analyser\MutatingScope
{

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
