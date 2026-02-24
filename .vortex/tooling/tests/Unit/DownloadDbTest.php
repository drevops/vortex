<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class DownloadDbTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envSet('VORTEX_DOWNLOAD_DB_SOURCE', 'url');
    $this->envSet('VORTEX_DOWNLOAD_DB_FORCE', '');
    $this->envSet('VORTEX_DOWNLOAD_DB_PROCEED', '1');
    $this->envSet('VORTEX_DOWNLOAD_DB_FILE', 'db.sql');
    $this->envSet('VORTEX_DOWNLOAD_DB_DIR', self::$tmp . '/data');
    $this->envSet('VORTEX_DOWNLOAD_DB_SEMAPHORE', '');
  }

  #[DataProvider('dataProviderEarlyPass')]
  public function testEarlyPass(\Closure $before, string $expected): void {
    $before($this);

    $this->runScriptEarlyPass('src/download-db', $expected);
  }

  public static function dataProviderEarlyPass(): array {
    return [
      'proceed not set' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_DOWNLOAD_DB_PROCEED', '0');
        },
        'expected' => 'Skipping database download',
      ],
      'existing dump skips download' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          File::mkdir($db_dir);
          File::dump($db_dir . '/db.sql', 'fake-dump');
          $test->mockPassthru([
            'cmd' => 'ls -Alh ' . escapeshellarg($db_dir) . ' 2>/dev/null || true',
            'result_code' => 0,
          ]);
        },
        'expected' => 'Download will not proceed',
      ],
    ];
  }

  #[DataProvider('dataProviderSuccess')]
  public function testSuccess(\Closure $before, array $expected, ?\Closure $after = NULL): void {
    $before($this);

    $output = $this->runScript('src/download-db');

    foreach ($expected as $str) {
      $this->assertStringContainsString($str, $output);
    }

    if ($after instanceof \Closure) {
      $after($this);
    }
  }

  public static function dataProviderSuccess(): array {
    return [
      'default url source' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          $test->mockPassthruMultiple([
            ['cmd' => self::$srcDir . '/download-db-url', 'result_code' => 0],
            ['cmd' => 'ls -Alh ' . escapeshellarg($db_dir) . ' || true', 'result_code' => 0],
          ]);
        },
        'expected' => ['Started database download.', 'Finished database download.'],
        'after' => NULL,
      ],
      'ftp source' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          $test->envSet('VORTEX_DOWNLOAD_DB_SOURCE', 'ftp');
          $test->mockPassthruMultiple([
            ['cmd' => self::$srcDir . '/download-db-ftp', 'result_code' => 0],
            ['cmd' => 'ls -Alh ' . escapeshellarg($db_dir) . ' || true', 'result_code' => 0],
          ]);
        },
        'expected' => ['Finished database download.'],
        'after' => NULL,
      ],
      'existing dump force override' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          File::mkdir($db_dir);
          File::dump($db_dir . '/db.sql', 'fake-dump');
          $test->envSet('VORTEX_DOWNLOAD_DB_FORCE', '1');
          $test->mockPassthruMultiple([
            ['cmd' => 'ls -Alh ' . escapeshellarg($db_dir) . ' 2>/dev/null || true', 'result_code' => 0],
            ['cmd' => self::$srcDir . '/download-db-url', 'result_code' => 0],
            ['cmd' => 'ls -Alh ' . escapeshellarg($db_dir) . ' || true', 'result_code' => 0],
          ]);
        },
        'expected' => ['Will download a fresh copy', 'Finished database download.'],
        'after' => NULL,
      ],
      'semaphore created' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          $test->envSet('VORTEX_DOWNLOAD_DB_SEMAPHORE', self::$tmp . '/sem');
          $test->mockPassthruMultiple([
            ['cmd' => self::$srcDir . '/download-db-url', 'result_code' => 0],
            ['cmd' => 'ls -Alh ' . escapeshellarg($db_dir) . ' || true', 'result_code' => 0],
          ]);
        },
        'expected' => ['Finished database download.'],
        'after' => function (self $test): void {
          $test->assertFileExists(self::$tmp . '/sem');
        },
      ],
    ];
  }

  #[DataProvider('dataProviderError')]
  public function testError(\Closure $before, string $expected): void {
    $before($this);

    $this->runScriptError('src/download-db', $expected);
  }

  public static function dataProviderError(): array {
    return [
      'invalid source' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_DOWNLOAD_DB_SOURCE', 'invalid');
        },
        'expected' => 'Invalid database download source',
      ],
      'source script fails' => [
        'before' => function (self $test): void {
          $test->mockPassthru([
            'cmd' => self::$srcDir . '/download-db-url',
            'result_code' => 1,
          ]);
        },
        'expected' => 'Failed to download database',
      ],
    ];
  }

}
