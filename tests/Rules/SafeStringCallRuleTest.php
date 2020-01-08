<?php declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<SafeStringCallRule>
 */
class SafeStringCallRuleTest extends RuleTestCase
{
    /** @override */
	protected function getRule(): Rule
	{
		return new SafeStringCallRule(
            [
                'query' => 0,
                'Nish\PHPStan\Test\SqlString::__construct' => 1,
                'Nish\PHPStan\Test\SqlString::append' => 0,
                'Nish\PHPStan\Test\SqlString::create' => 1,
            ],
			new RuleLevelHelper($this->createBroker(), true, false, true)
		);
	}

	public function testSafeStringCallRule(): void
	{
		$this->analyse([__DIR__ . '/data/safestringcall.php'], [
			[
				'Parameter #1 (string) is not safe-string.',
				17,
			],
			[
				'Parameter #2 (string) is not safe-string.',
				19,
			],
			[
				'Parameter #1 (string) is not safe-string.',
				23,
			],
			[
				'Parameter #1 (string|null) is not safe-string.',
				27,
			],
			[
				'Parameter #1 (string) is not safe-string.',
				37,
			],
			[
				'Parameter #2 (string) is not safe-string.',
				41,
			],
		]);
	}
}
