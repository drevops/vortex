<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class ExportDbTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envSet('VORTEX_EXPORT_DB_IMAGE', '');
    $this->envUnset('VORTEX_DB_IMAGE');
    $this->envSet('VORTEX_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED', '0');

    $GLOBALS['argv'] = ['export-db'];
  }

  /**
   * Override to make $argv available in the require'd scope.
   *
   * The export-db script uses bare $argv (not $GLOBALS['argv']), which is
   * only available in the global scope. When require'd inside a function,
   * we need to define it as a local variable.
   */
  protected function runScript(string $script_path, ?int $early_exit_code = NULL): string {
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

    chdir($root);

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

    $output = $this->runScript('src/export-db');

    foreach ($expected as $str) {
      $this->assertStringContainsString($str, $output);
    }
  }

  public static function dataProviderSuccess(): array {
    return [
      'file export' => [
        'before' => function (self $test): void {
          $test->mockPassthru([
            'cmd' => 'docker compose exec -T cli php /export-db-file ',
            'result_code' => 0,
          ]);
        },
        'expected' => ['Started database export.', 'Finished database export.'],
      ],
      'image export' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_EXPORT_DB_IMAGE', 'myorg/mydb');
          $test->mockPassthru([
            'cmd' => self::$srcDir . '/export-db-image ',
            'result_code' => 0,
          ]);
        },
        'expected' => ['Started database export.', 'Finished database export.'],
      ],
      'image export with registry deploy' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_EXPORT_DB_IMAGE', 'myorg/mydb');
          $test->envSet('VORTEX_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED', '1');
          $test->mockPassthruMultiple([
            ['cmd' => self::$srcDir . '/export-db-image ', 'result_code' => 0],
            ['cmd' => self::$srcDir . '/deploy-container-registry', 'result_code' => 0],
          ]);
        },
        'expected' => ['Finished database export.'],
      ],
    ];
  }

  #[DataProvider('dataProviderError')]
  public function testError(\Closure $before, string $expected): void {
    $before($this);

    $this->runScriptError('src/export-db', $expected);
  }

  public static function dataProviderError(): array {
    return [
      'file export fails' => [
        'before' => function (self $test): void {
          $test->mockPassthru([
            'cmd' => 'docker compose exec -T cli php /export-db-file ',
            'result_code' => 1,
          ]);
        },
        'expected' => 'Failed to export database as file',
      ],
      'image export fails' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_EXPORT_DB_IMAGE', 'myorg/mydb');
          $test->mockPassthru([
            'cmd' => self::$srcDir . '/export-db-image ',
            'result_code' => 1,
          ]);
        },
        'expected' => 'Failed to export database as image',
      ],
      'registry deploy fails' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_EXPORT_DB_IMAGE', 'myorg/mydb');
          $test->envSet('VORTEX_EXPORT_DB_CONTAINER_REGISTRY_DEPLOY_PROCEED', '1');
          $test->mockPassthruMultiple([
            ['cmd' => self::$srcDir . '/export-db-image ', 'result_code' => 0],
            ['cmd' => self::$srcDir . '/deploy-container-registry', 'result_code' => 1],
          ]);
        },
        'expected' => 'Failed to deploy container image',
      ],
    ];
  }

}
