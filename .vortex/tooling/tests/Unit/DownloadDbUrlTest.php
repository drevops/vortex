<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class DownloadDbUrlTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_DOWNLOAD_DB_URL', 'https://example.com/db.sql');
    $this->envSet('VORTEX_DOWNLOAD_DB_URL_DB_DIR', self::$tmp . '/data');
    $this->envSet('VORTEX_DOWNLOAD_DB_URL_DB_FILE', 'db.sql');
    $this->envSet('VORTEX_DOWNLOAD_DB_UNZIP_PASSWORD', '');
  }

  #[DataProvider('dataProviderSuccess')]
  public function testSuccess(\Closure $before, array $expected, ?\Closure $after = NULL): void {
    $before($this);

    $output = $this->runScript('src/download-db-url');

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
          $test->mockRequestMultiple([
            ['url' => 'https://example.com/db.sql', 'method' => 'GET', 'response' => []],
          ]);
        },
        'expected' => ['Started database dump download from URL.', 'Finished database dump download from URL.'],
        'after' => NULL,
      ],
      'directory creation' => [
        'before' => function (self $test): void {
          $test->mockRequestMultiple([
            ['url' => 'https://example.com/db.sql', 'method' => 'GET', 'response' => []],
          ]);
        },
        'expected' => ['Finished database dump download from URL.'],
        'after' => function (self $test): void {
          $test->assertDirectoryExists(self::$tmp . '/data');
        },
      ],
      'zip extraction' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          File::mkdir($db_dir);
          $test->envSet('VORTEX_DOWNLOAD_DB_URL', 'https://example.com/db.zip');

          $mock = $test->getFunctionMock('DrevOps\\VortexTooling', 'getmypid');
          $mock->expects($test->any())->willReturn(12345);

          $temp_dir = $db_dir . '/tmp_extract_12345';
          File::mkdir($temp_dir);
          File::dump($temp_dir . '/extracted_db.sql', 'SQL CONTENT');

          $test->mockRequestMultiple([
            ['url' => 'https://example.com/db.zip', 'method' => 'GET', 'response' => []],
          ]);
          $test->mockPassthru([
            'cmd' => sprintf('unzip -o %s -d %s', escapeshellarg($db_dir . '/db.sql.zip'), escapeshellarg($temp_dir)),
            'result_code' => 0,
          ]);
          $test->mockShellExecMultiple([
            ['value' => $temp_dir . '/extracted_db.sql' . "\n"],
            ['value' => ''],
          ]);
        },
        'expected' => ['Detecting zip file', 'Finished database dump download from URL.'],
        'after' => NULL,
      ],
      'zip extraction with password' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          File::mkdir($db_dir);
          $test->envSet('VORTEX_DOWNLOAD_DB_URL', 'https://example.com/db.zip');
          $test->envSet('VORTEX_DOWNLOAD_DB_UNZIP_PASSWORD', 'secret');

          $mock = $test->getFunctionMock('DrevOps\\VortexTooling', 'getmypid');
          $mock->expects($test->any())->willReturn(12345);

          $temp_dir = $db_dir . '/tmp_extract_12345';
          File::mkdir($temp_dir);
          File::dump($temp_dir . '/extracted_db.sql', 'SQL CONTENT');

          $test->mockRequestMultiple([
            ['url' => 'https://example.com/db.zip', 'method' => 'GET', 'response' => []],
          ]);
          $test->mockPassthru([
            'cmd' => sprintf('unzip -o -P %s %s -d %s', escapeshellarg('secret'), escapeshellarg($db_dir . '/db.sql.zip'), escapeshellarg($temp_dir)),
            'result_code' => 0,
          ]);
          $test->mockShellExecMultiple([
            ['value' => $temp_dir . '/extracted_db.sql' . "\n"],
            ['value' => ''],
          ]);
        },
        'expected' => ['Unzipping password-protected', 'Finished database dump download from URL.'],
        'after' => NULL,
      ],
    ];
  }

  #[DataProvider('dataProviderError')]
  public function testError(\Closure $before, string $expected): void {
    $before($this);

    $this->runScriptError('src/download-db-url', $expected);
  }

  public static function dataProviderError(): array {
    return [
      'missing url' => [
        'before' => function (self $test): void {
          $test->envSet('VORTEX_DOWNLOAD_DB_URL', '');
        },
        'expected' => 'Missing required value for VORTEX_DOWNLOAD_DB_URL',
      ],
      'request fails' => [
        'before' => function (self $test): void {
          File::mkdir(self::$tmp . '/data');
          $test->mockRequestMultiple([
            ['url' => 'https://example.com/db.sql', 'method' => 'GET', 'response' => ['ok' => FALSE, 'status' => 500]],
          ]);
        },
        'expected' => 'Failed to download database dump from URL',
      ],
      'zip extraction fails' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          File::mkdir($db_dir);
          $test->envSet('VORTEX_DOWNLOAD_DB_URL', 'https://example.com/db.zip');

          $mock = $test->getFunctionMock('DrevOps\\VortexTooling', 'getmypid');
          $mock->expects($test->any())->willReturn(12345);

          $temp_dir = $db_dir . '/tmp_extract_12345';

          $test->mockRequestMultiple([
            ['url' => 'https://example.com/db.zip', 'method' => 'GET', 'response' => []],
          ]);
          $test->mockPassthru([
            'cmd' => sprintf('unzip -o %s -d %s', escapeshellarg($db_dir . '/db.sql.zip'), escapeshellarg($temp_dir)),
            'result_code' => 1,
          ]);
          $test->mockShellExec('');
        },
        'expected' => 'Failed to extract zip file',
      ],
      'zip no files found' => [
        'before' => function (self $test): void {
          $db_dir = self::$tmp . '/data';
          File::mkdir($db_dir);
          $test->envSet('VORTEX_DOWNLOAD_DB_URL', 'https://example.com/db.zip');

          $mock = $test->getFunctionMock('DrevOps\\VortexTooling', 'getmypid');
          $mock->expects($test->any())->willReturn(12345);

          $temp_dir = $db_dir . '/tmp_extract_12345';
          File::mkdir($temp_dir);

          $test->mockRequestMultiple([
            ['url' => 'https://example.com/db.zip', 'method' => 'GET', 'response' => []],
          ]);
          $test->mockPassthru([
            'cmd' => sprintf('unzip -o %s -d %s', escapeshellarg($db_dir . '/db.sql.zip'), escapeshellarg($temp_dir)),
            'result_code' => 0,
          ]);
          $test->mockShellExecMultiple([
            ['value' => ''],
            ['value' => ''],
          ]);
        },
        'expected' => 'No files found in the zip archive',
      ],
    ];
  }

}
