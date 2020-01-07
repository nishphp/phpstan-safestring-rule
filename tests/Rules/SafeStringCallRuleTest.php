<?php declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolver;

/**
 * @extends \PHPStan\Testing\RuleTestCase<SafeStringCallRule>
 */
class SafeStringCallRuleTest extends RuleTestCase
{
    /** array<string,int> */
    private $patterns = [];

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
        require_once(__DIR__ . '/data/safestringcall.php');
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
