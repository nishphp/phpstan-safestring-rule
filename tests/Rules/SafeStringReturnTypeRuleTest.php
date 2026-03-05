<?php declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<SafeStringReturnTypeRule>
 */
class SafeStringReturnTypeRuleTest extends RuleTestCase
{
    /** @override */
	protected function getRule(): Rule
	{

	/** @var RuleLevelHelper $ruleLevelHelper */
    	$ruleLevelHelper = self::getContainer()->getByType(RuleLevelHelper::class);

		return new SafeStringReturnTypeRule(
            [
                'getQuery1',
                'getQuery2',
                'getQuery3',
                'getQuery4',
                'getQuery5',
                'ReturnTypes::getSafe',
                'ReturnTypes::getRaw',
            ],
            new FunctionReturnTypeCheck($ruleLevelHelper)
		);
	}

	public function testSafeStringReturnTypeRule(): void
	{
		$this->analyse([__DIR__ . '/data/safestringreturntype.php'], [
			[
				'Function getQuery1() should return safe-string but returns string.',
				7,
			],
			[
				'Function getQuery4() should return safe-string but returns string|null.',
				19,
			],
			[
				'Function getQuery5() should return safe-string but returns non-falsy-string.',
				23,
			],
			[
				'Method ReturnTypes::getRaw() should return safe-string but returns non-falsy-string.',
				35,
			],
		]);
	}
}
