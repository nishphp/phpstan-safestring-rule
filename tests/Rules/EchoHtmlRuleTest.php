<?php declare(strict_types = 1);

namespace Nish\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPStan\PhpDoc\PhpDocNodeResolver;
use PHPStan\PhpDoc\TypeNodeResolver;

use Closure;

/**
 * @extends \PHPStan\Testing\RuleTestCase<EchoHtmlRule>
 */
class EchoHtmlRuleTest extends RuleTestCase
{

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/../phpstan.neon'];
	}


    /** @override */
	protected function getRule(): Rule
	{
		/** @var RuleLevelHelper $ruleLevelHelper */
		$ruleLevelHelper = self::getContainer()->getByType(RuleLevelHelper::class);

		return new EchoHtmlRule($ruleLevelHelper);
	}

	public function testEchoHtmlRule(): void
	{
		$this->analyse([__DIR__ . '/data/echohtml.php'], [
			[
				'echo() Parameter #1 (string) is not safehtml-string.',
				15,
			],
			[
				'echo() Parameter #1 (string|null) is not safehtml-string.',
				25,
			],
			[
				'echo() Parameter #1 (bool|float|int|string) is not safehtml-string.',
				31,
			],
			[
				'echo() Parameter #1 (string) is not safehtml-string.',
				36,
			],
		]);
	}
}
