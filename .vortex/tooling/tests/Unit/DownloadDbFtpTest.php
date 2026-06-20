<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class DownloadDbFtpTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_DOWNLOAD_DB_FTP_USER', 'testuser');
    $this->envSet('VORTEX_DOWNLOAD_DB_FTP_PASS', 'testpass');
    $this->envSet('VORTEX_DOWNLOAD_DB_FTP_HOST', 'ftp.example.com');
    $this->envSet('VORTEX_DOWNLOAD_DB_FTP_PORT', '21');
    $this->envSet('VORTEX_DOWNLOAD_DB_FTP_FILE', 'backups/db.sql');
    $this->envSet('VORTEX_DOWNLOAD_DB_FTP_DB_DIR', self::$tmp . '/data');
    $this->envSet('VORTEX_DOWNLOAD_DB_FTP_DB_FILE', 'db.sql');
  }

  #[DataProvider('dataProviderSuccess')]
  public function testSuccess(?\Closure $before, array $expected, ?\Closure $after = NULL): void {
    if ($before instanceof \Closure) {
      $before($this);
    }

    $this->mockRequestMultiple([
      ['url' => 'ftp://ftp.example.com:21/backups/db.sql', 'method' => 'GET', 'response' => []],
    ]);

    $output = $this->runScript('src/download-db-ftp');

    foreach ($expected as $str) {
      $this->assertStringContainsString($str, $output);
    }

    if ($after instanceof \Closure) {
      $after($this);
    }
  }

  public static function dataProviderSuccess(): array {
    return [
      'success' => [
        'before' => function (self $test): void {
          File::mkdir(self::$tmp . '/data');
        },
        'expected' => ['Started database dump download from FTP.', 'Finished database dump download from FTP.'],
        'after' => function (self $test): void {
          $test->assertFileExists(self::$tmp . '/data/db.sql');
        },
      ],
      'directory creation' => [
        'before' => NULL,
        'expected' => ['Finished database dump download from FTP.'],
        'after' => function (self $test): void {
          $test->assertDirectoryExists(self::$tmp . '/data');
        },
      ],
    ];
  }

  #[DataProvider('dataProviderError')]
  public function testError(\Closure $before, string $expected): void {
    $before($this);

    $this->runScriptError('src/download-db-ftp', $expected);
  }

  public static function dataProviderError(): array {
    return [
      'missing user' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_DOWNLOAD_DB_FTP_USER', '');
        },
        'expected' => 'Missing required value for VORTEX_DOWNLOAD_DB_FTP_USER',
      ],
      'missing pass' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_DOWNLOAD_DB_FTP_PASS', '');
        },
        'expected' => 'Missing required value for VORTEX_DOWNLOAD_DB_FTP_PASS',
      ],
      'missing host' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_DOWNLOAD_DB_FTP_HOST', '');
        },
        'expected' => 'Missing required value for VORTEX_DOWNLOAD_DB_FTP_HOST',
      ],
      'missing port' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_DOWNLOAD_DB_FTP_PORT', '');
        },
        'expected' => 'Missing required value for VORTEX_DOWNLOAD_DB_FTP_PORT',
      ],
      'missing file' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_DOWNLOAD_DB_FTP_FILE', '');
        },
        'expected' => 'Missing required value for VORTEX_DOWNLOAD_DB_FTP_FILE',
      ],
      'request fails' => [
        'before' => function (self $test): void {
          File::mkdir(self::$tmp . '/data');
          $test->mockRequestMultiple([
            ['url' => 'ftp://ftp.example.com:21/backups/db.sql', 'method' => 'GET', 'response' => ['ok' => FALSE, 'status' => 550]],
          ]);
        },
        'expected' => 'Failed to download database dump from FTP',
      ],
    ];
  }

}
