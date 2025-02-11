<?php declare(strict_types = 1);

namespace Nish\PHPStan\Test;

use Nette\Utils\Json;
use PHPStan\ShouldNotHappenException;
use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    private const ERROR_ARGUMENT_TYPE = [
        'ignorable' => true,
        'identifier' => 'argument.type'
    ];

    private const ERROR_BASE = [
        [
            'message' => 'Parameter #1 $s of function Base\\s expects safe-string, string given.',
            'line' => 7,
        ] + self::ERROR_ARGUMENT_TYPE,
        [
            'message' => 'Parameter #1 $s of function Base\\s expects safe-string, string given.',
            'line' => 34,
        ] + self::ERROR_ARGUMENT_TYPE,
    ];

    private const ERROR_FUNC = [
        [
            'message' => 'Parameter #1 $s of function Func\\s expects safe-string, string given.',
            'line' => 23,
        ] + self::ERROR_ARGUMENT_TYPE,
        [
            'message' => 'Parameter #1 $s of function Func\\s expects safe-string, string given.',
            'line' => 40,
        ] + self::ERROR_ARGUMENT_TYPE,
    ];

    private const ERROR_ARRAY = [
        [
            'message' => 'echo() Parameter #1 (string) is not safehtml-string.',
            'line' => 48,
            'ignorable' => true,
        ],
    ];

	public function testAll(): void
	{
		$output = $this->runPhpStan(__DIR__ . '/integration/', __DIR__ . '/integration/integration.neon');
		$errors = Json::decode($output, Json::FORCE_ARRAY);

        //var_dump($errors);

        $messages = $errors['files'][__DIR__ . '/integration/base.php']['messages'];
        $this->assertSame(self::ERROR_BASE, $messages);

        $messages = $errors['files'][__DIR__ . '/integration/function.php']['messages'];
        $this->assertSame(self::ERROR_FUNC, $messages);

        $messages = $errors['files'][__DIR__ . '/integration/array-bug1.php']['messages'];
        $this->assertSame(self::ERROR_ARRAY, $messages);


        $this->assertSame(0, $errors['totals']['errors']);
        $this->assertSame(5, $errors['totals']['file_errors']);
	}


    /** @see PHPStan\Command\ErrorFormatter\BaselineNeonErrorFormatterIntegrationTest */
	private function runPhpStan(
		string $analysedPath,
		?string $configFile,
		string $errorFormatter = 'json',
		?string $baselineFile = null,
	): string
	{
		$originalDir = getcwd();
		if ($originalDir === false) {
			throw new ShouldNotHappenException();
		}
		chdir(__DIR__ . '/..');
		exec(sprintf('%s %s clear-result-cache %s', escapeshellarg(PHP_BINARY), 'vendor/bin/phpstan', $configFile !== null ? '--configuration ' . escapeshellarg($configFile) : ''), $clearResultCacheOutputLines, $clearResultCacheExitCode);
		if ($clearResultCacheExitCode !== 0) {
			throw new ShouldNotHappenException('Could not clear result cache.');
		}

		exec(sprintf('%s %s analyse --no-progress --error-format=%s --level=7 %s %s%s', escapeshellarg(PHP_BINARY), 'vendor/bin/phpstan', $errorFormatter, $configFile !== null ? '--configuration ' . escapeshellarg($configFile) : '', escapeshellarg($analysedPath), $baselineFile !== null ? ' --generate-baseline ' . escapeshellarg($baselineFile) : ''), $outputLines);
		chdir($originalDir);

		return implode("\n", $outputLines);
	}
}
