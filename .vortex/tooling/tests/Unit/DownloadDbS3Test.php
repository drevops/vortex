<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class DownloadDbS3Test extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY', 'AKIAIOSFODNN7EXAMPLE');
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_SECRET_KEY', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_BUCKET', 'mybucket');
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_REGION', 'us-east-1');
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_DB_DIR', self::$tmp . '/data');
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_DB_FILE', 'db.sql');
  }

  public function testMissingAccessKey(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY', '');
    $this->envUnset('S3_ACCESS_KEY');

    $this->runScriptError('src/download-db-s3', 'Missing required value for VORTEX_DOWNLOAD_DB_S3_ACCESS_KEY');
  }

  public function testMissingSecretKey(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_SECRET_KEY', '');
    $this->envUnset('S3_SECRET_KEY');

    $this->runScriptError('src/download-db-s3', 'Missing required value for VORTEX_DOWNLOAD_DB_S3_SECRET_KEY');
  }

  public function testMissingBucket(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_BUCKET', '');
    $this->envUnset('S3_BUCKET');

    $this->runScriptError('src/download-db-s3', 'Missing required value for VORTEX_DOWNLOAD_DB_S3_BUCKET');
  }

  public function testMissingRegion(): void {
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_REGION', '');
    $this->envUnset('S3_REGION');

    $this->runScriptError('src/download-db-s3', 'Missing required value for VORTEX_DOWNLOAD_DB_S3_REGION');
  }

  public function testSuccess(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $gmdate_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'gmdate');
    $gmdate_mock->expects($this->any())->willReturnCallback(function ($format): string {
      if ($format === 'Ymd') {
        return '20240101';
      }
      return '20240101T120000Z';
    });

    $this->mockRequestMultiple([
      [
        'url' => 'https://mybucket.s3.us-east-1.amazonaws.com/db.sql',
        'method' => 'GET',
        'response' => ['body' => 'SQL DUMP CONTENT'],
      ],
    ]);

    $output = $this->runScript('src/download-db-s3');

    $this->assertStringContainsString('Started database dump download from S3.', $output);
    $this->assertStringContainsString('Finished database dump download from S3.', $output);
    $this->assertFileExists(self::$tmp . '/data/db.sql');
  }

  public function testSuccessWithPrefix(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);
    $this->envSet('VORTEX_DOWNLOAD_DB_S3_PREFIX', 'backups/daily');

    $gmdate_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'gmdate');
    $gmdate_mock->expects($this->any())->willReturnCallback(function ($format): string {
      if ($format === 'Ymd') {
        return '20240101';
      }
      return '20240101T120000Z';
    });

    $this->mockRequestMultiple([
      [
        'url' => 'https://mybucket.s3.us-east-1.amazonaws.com/backups/daily/db.sql',
        'method' => 'GET',
        'response' => ['body' => 'SQL DUMP CONTENT'],
      ],
    ]);

    $output = $this->runScript('src/download-db-s3');

    $this->assertStringContainsString('S3 prefix:', $output);
    $this->assertStringContainsString('Finished database dump download from S3.', $output);
  }

  public function testRequestFails(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $gmdate_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'gmdate');
    $gmdate_mock->expects($this->any())->willReturnCallback(function ($format): string {
      if ($format === 'Ymd') {
        return '20240101';
      }
      return '20240101T120000Z';
    });

    $this->mockRequestMultiple([
      [
        'url' => 'https://mybucket.s3.us-east-1.amazonaws.com/db.sql',
        'method' => 'GET',
        'response' => ['ok' => FALSE, 'status' => 403, 'body' => ''],
      ],
    ]);

    $this->runScriptError('src/download-db-s3', 'Failed to download database dump from S3');
  }

  public function testDirectoryCreation(): void {
    $gmdate_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'gmdate');
    $gmdate_mock->expects($this->any())->willReturnCallback(function ($format): string {
      if ($format === 'Ymd') {
        return '20240101';
      }
      return '20240101T120000Z';
    });

    $this->mockRequestMultiple([
      [
        'url' => 'https://mybucket.s3.us-east-1.amazonaws.com/db.sql',
        'method' => 'GET',
        'response' => ['body' => 'SQL DUMP CONTENT'],
      ],
    ]);

    $output = $this->runScript('src/download-db-s3');

    $this->assertTrue(is_dir(self::$tmp . '/data'));
    $this->assertStringContainsString('Finished database dump download from S3.', $output);
  }

}
