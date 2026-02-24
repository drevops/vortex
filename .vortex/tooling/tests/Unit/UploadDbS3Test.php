<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class UploadDbS3Test extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_UPLOAD_DB_S3_ACCESS_KEY', 'AKIAIOSFODNN7EXAMPLE');
    $this->envSet('VORTEX_UPLOAD_DB_S3_SECRET_KEY', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY');
    $this->envSet('VORTEX_UPLOAD_DB_S3_BUCKET', 'mybucket');
    $this->envSet('VORTEX_UPLOAD_DB_S3_REGION', 'us-east-1');
    $this->envSet('VORTEX_UPLOAD_DB_S3_DB_DIR', self::$tmp . '/data');
    $this->envSet('VORTEX_UPLOAD_DB_S3_DB_FILE', 'db.sql');
  }

  public function testMissingAccessKey(): void {
    $this->envSet('VORTEX_UPLOAD_DB_S3_ACCESS_KEY', '');
    $this->envUnset('S3_ACCESS_KEY');

    $this->runScriptError('src/upload-db-s3', 'Missing required value for VORTEX_UPLOAD_DB_S3_ACCESS_KEY');
  }

  public function testMissingSecretKey(): void {
    $this->envSet('VORTEX_UPLOAD_DB_S3_SECRET_KEY', '');
    $this->envUnset('S3_SECRET_KEY');

    $this->runScriptError('src/upload-db-s3', 'Missing required value for VORTEX_UPLOAD_DB_S3_SECRET_KEY');
  }

  public function testMissingBucket(): void {
    $this->envSet('VORTEX_UPLOAD_DB_S3_BUCKET', '');
    $this->envUnset('S3_BUCKET');

    $this->runScriptError('src/upload-db-s3', 'Missing required value for VORTEX_UPLOAD_DB_S3_BUCKET');
  }

  public function testMissingRegion(): void {
    $this->envSet('VORTEX_UPLOAD_DB_S3_REGION', '');
    $this->envUnset('S3_REGION');

    $this->runScriptError('src/upload-db-s3', 'Missing required value for VORTEX_UPLOAD_DB_S3_REGION');
  }

  public function testMissingLocalFile(): void {
    mkdir(self::$tmp . '/data', 0755, TRUE);

    $this->runScriptError('src/upload-db-s3', 'Database dump file');
  }

  public function testSuccess(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);
    file_put_contents($db_dir . '/db.sql', 'SQL DUMP DATA');

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
        'method' => 'PUT',
        'response' => ['body' => ''],
      ],
    ]);

    $output = $this->runScript('src/upload-db-s3');

    $this->assertStringContainsString('Started database dump upload to S3.', $output);
    $this->assertStringContainsString('Finished database dump upload to S3.', $output);
  }

  public function testSuccessWithPrefix(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);
    file_put_contents($db_dir . '/db.sql', 'SQL DUMP DATA');

    $this->envSet('VORTEX_UPLOAD_DB_S3_PREFIX', 'backups/daily');

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
        'method' => 'PUT',
        'response' => ['body' => ''],
      ],
    ]);

    $output = $this->runScript('src/upload-db-s3');

    $this->assertStringContainsString('S3 prefix:', $output);
    $this->assertStringContainsString('Finished database dump upload to S3.', $output);
  }

  public function testRequestFails(): void {
    $db_dir = self::$tmp . '/data';
    mkdir($db_dir, 0755, TRUE);
    file_put_contents($db_dir . '/db.sql', 'SQL DUMP DATA');

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
        'method' => 'PUT',
        'response' => ['ok' => FALSE, 'status' => 403, 'body' => ''],
      ],
    ]);

    $this->runScriptError('src/upload-db-s3', 'Failed to upload database dump to S3');
  }

}
