<?php declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<EchoRule>
 */
class EchoHtmlRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EchoHtmlRule(
			new RuleLevelHelper($this->createBroker(), true, false, true)
		);
	}

	public function testEchoHtmlRule(): void
	{
		$this->analyse([__DIR__ . '/data/echohtml.php'], [
			[
				'Parameter #1 (string) is not safehtml-string.',
				15,
			],
		]);
	}
}
