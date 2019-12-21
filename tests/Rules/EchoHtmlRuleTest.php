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
	public function setUp(): void
	{
        $resolver = self::getContainer()
                  ->getByType(TypeNodeResolver::class);
        Closure::bind(function(){
            $this->extensions[]
                = new \Nish\PHPStan\PhpDoc\TypeNodeResolverExtension();
        }, $resolver, TypeNodeResolver::class)->__invoke();

        // TODO: Where has the function of getTypeNodeResolverExtensions moved?
	}

    /** @override */
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
			[
				'Parameter #1 (string|null) is not safehtml-string.',
				25,
			],
			[
				'Parameter #1 (bool|float|int|string) is not safehtml-string.',
				31,
			],
			[
				'Parameter #1 (string) is not safehtml-string.',
				36,
			],
		]);
	}
}
