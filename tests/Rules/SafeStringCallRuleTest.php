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
			new RuleLevelHelper($this->createReflectionProvider(), true, false, true, true, true, true)
		);
	}

	public function testSafeStringCallRule(): void
	{
		$this->analyse([__DIR__ . '/data/safestringcall.php'], [
			[
				'query() Parameter #1 (non-falsy-string) is not safe-string.',
				17,
			],
			[
				'Nish\PHPStan\Test\SqlString::__construct() Parameter #2 (non-falsy-string) is not safe-string.',
				19,
			],
			[
				'query() Parameter #1 (non-falsy-string) is not safe-string.',
				23,
			],
			[
				'query() Parameter #1 (string|null) is not safe-string.',
				27,
			],
			[
				'Nish\PHPStan\Test\SqlString::append() Parameter #1 (string) is not safe-string.',
				37,
			],
			[
				'Nish\PHPStan\Test\SqlString::create() Parameter #2 (string) is not safe-string.',
				41,
			],
		]);
	}
}
