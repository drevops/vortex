<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class ImportDbTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envUnset('RUN_ON_HOST');

    $GLOBALS['argv'] = ['import-db'];
  }

  /**
   * Override to make $argv available in the require'd scope.
   *
   * The import-db script uses bare $argv (not $GLOBALS['argv']), which is
   * only available in the global scope. When require'd inside a function,
   * we need to define it as a local variable.
   */
  protected function runScript(string $script_path, ?int $early_exit_code = NULL, ?string $cwd = NULL): string {
    // @phpcs:ignore DrupalPractice.CodeAnalysis.VariableAnalysis.UnusedVariable
    $argv = $GLOBALS['argv'] ?? [];

    ob_start();

    $original_dir = getcwd();
    if ($original_dir === FALSE) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException('Failed to get current working directory.');
      // @codeCoverageIgnoreEnd
    }

    $root = __DIR__ . '/../../src';
    if (!file_exists($root)) {
      // @codeCoverageIgnoreStart
      throw new \RuntimeException('Root directory not found: ' . $root);
      // @codeCoverageIgnoreEnd
    }

    chdir($cwd ?? $root);

    if (!is_null($early_exit_code)) {
      if ($early_exit_code > 0) {
        $this->mockQuit($early_exit_code);
        $this->expectException(QuitErrorException::class);
      }
      else {
        $this->mockQuit(0);
        $this->expectException(QuitSuccessException::class);
      }
    }

    $output = '';
    $cleared_buffer = FALSE;
    try {
      require __DIR__ . '/../../' . $script_path;
    }
    catch (QuitSuccessException $e) {
      $output = ob_get_clean() ?: '';
      $cleared_buffer = TRUE;
      throw new QuitSuccessException($e->getCode(), $output);
    }
    catch (QuitErrorException $e) {
      $output = ob_get_clean() ?: '';
      $cleared_buffer = TRUE;
      throw new QuitErrorException($e->getCode(), $output);
    }
    finally {
      if (!$cleared_buffer) {
        $output = ob_get_clean() ?: '';
      }
      chdir($original_dir);
    }

    return $output;
  }

  #[DataProvider('dataProviderSuccess')]
  public function testSuccess(\Closure $before, array $expected): void {
    $before($this);

    $output = $this->runScript('src/vortex-import-db');

    foreach ($expected as $str) {
      $this->assertStringContainsString($str, $output);
    }
  }

  public static function dataProviderSuccess(): array {
    return [
      'container file import' => [
        'before' => function (self $test): void {
          $test->envSet('RUN_ON_HOST', '0');
          $test->mockPassthru([
            'cmd' => self::$srcDir . '/vortex-import-db-file ',
            'result_code' => 0,
          ]);
        },
        'expected' => ['Started database import.', 'Finished database import.'],
      ],
      'host file import' => [
        'before' => function (self $test): void {
          $test->envSet('RUN_ON_HOST', '1');
          $test->mockPassthru([
            'cmd' => 'docker compose exec -T cli php /vortex-import-db-file ',
            'result_code' => 0,
          ]);
        },
        'expected' => ['Started database import.', 'Finished database import.'],
      ],
      'host file import with detected docker' => [
        'before' => function (self $test): void {
          $test->mockCommandExists();
          $test->mockPassthru([
            'cmd' => 'docker compose exec -T cli php /vortex-import-db-file ',
            'result_code' => 0,
          ]);
        },
        'expected' => ['Started database import.', 'Finished database import.'],
      ],
      'host file import with argument' => [
        'before' => function (self $test): void {
          $test->envSet('RUN_ON_HOST', '1');
          $GLOBALS['argv'] = ['import-db', '.data/db_custom.sql'];
          $test->mockPassthru([
            'cmd' => "docker compose exec -T cli php /vortex-import-db-file '.data/db_custom.sql'",
            'result_code' => 0,
          ]);
        },
        'expected' => ['Started database import.', 'Finished database import.'],
      ],
    ];
  }

  #[DataProvider('dataProviderError')]
  public function testError(\Closure $before, string $expected): void {
    $before($this);

    $this->runScriptError('src/vortex-import-db', $expected);
  }

  public static function dataProviderError(): array {
    return [
      'container file import fails' => [
        'before' => function (self $test): void {
          $test->envSet('RUN_ON_HOST', '0');
          $test->mockPassthru([
            'cmd' => self::$srcDir . '/vortex-import-db-file ',
            'result_code' => 1,
          ]);
        },
        'expected' => 'Failed to import database from file',
      ],
      'host file import fails' => [
        'before' => function (self $test): void {
          $test->envSet('RUN_ON_HOST', '1');
          $test->mockPassthru([
            'cmd' => 'docker compose exec -T cli php /vortex-import-db-file ',
            'result_code' => 1,
          ]);
        },
        'expected' => 'Failed to import database from file',
      ],
    ];
  }

}
