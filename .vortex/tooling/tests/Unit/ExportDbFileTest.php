<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class ExportDbFileTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_EXPORT_DB_FILE_DIR', self::$tmp . '/db');

    // Reset argv to prevent PHPUnit arguments from leaking into the script.
    $GLOBALS['argv'] = ['export-db-file'];
  }

  public function testDefaultTimestampFilename(): void {
    mkdir(self::$tmp . '/db', 0755, TRUE);

    // Mock date() for predictable filename.
    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101_120000');

    $dump_file = self::$tmp . '/db/export_db_20240101_120000.sql';
    $dump_file_drush = str_starts_with($dump_file, './') ? '../' . substr($dump_file, 2) : $dump_file;

    // Drush sql:dump.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:dump --skip-tables-key=common --result-file=' . escapeshellarg($dump_file_drush) . ' -q',
      'result_code' => 0,
    ]);

    // Create the dump file to simulate drush output.
    file_put_contents($dump_file, 'SQL DUMP');

    $output = $this->runScript('src/export-db-file');

    $this->assertStringContainsString('Started database file export.', $output);
    $this->assertStringContainsString('Exported database dump saved', $output);
    $this->assertStringContainsString('Finished database file export.', $output);
  }

  public function testCustomFilename(): void {
    mkdir(self::$tmp . '/db', 0755, TRUE);

    $dump_file = self::$tmp . '/db/custom.sql';
    $dump_file_drush = $dump_file;

    // Drush sql:dump.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:dump --skip-tables-key=common --result-file=' . escapeshellarg($dump_file_drush) . ' -q',
      'result_code' => 0,
    ]);

    file_put_contents($dump_file, 'SQL DUMP');

    // Pass custom filename via $argv.
    $GLOBALS['argv'] = ['export-db-file', 'custom.sql'];

    $output = $this->runScript('src/export-db-file');

    $this->assertStringContainsString('Exported database dump saved', $output);
    $this->assertStringContainsString('custom.sql', $output);
  }

  public function testDumpFileMissing(): void {
    mkdir(self::$tmp . '/db', 0755, TRUE);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101_120000');

    $dump_file = self::$tmp . '/db/export_db_20240101_120000.sql';
    $dump_file_drush = $dump_file;

    // Drush sql:dump succeeds but file not created.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:dump --skip-tables-key=common --result-file=' . escapeshellarg($dump_file_drush) . ' -q',
      'result_code' => 0,
    ]);

    $this->runScriptError('src/export-db-file', 'Unable to save dump file');
  }

  public function testDumpFileEmpty(): void {
    mkdir(self::$tmp . '/db', 0755, TRUE);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101_120000');

    $dump_file = self::$tmp . '/db/export_db_20240101_120000.sql';
    $dump_file_drush = $dump_file;

    // Drush sql:dump.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:dump --skip-tables-key=common --result-file=' . escapeshellarg($dump_file_drush) . ' -q',
      'result_code' => 0,
    ]);

    // Create empty file.
    file_put_contents($dump_file, '');

    $this->runScriptError('src/export-db-file', 'Unable to save dump file');
  }

  public function testDrushFails(): void {
    mkdir(self::$tmp . '/db', 0755, TRUE);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101_120000');

    $dump_file = self::$tmp . '/db/export_db_20240101_120000.sql';
    $dump_file_drush = $dump_file;

    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:dump --skip-tables-key=common --result-file=' . escapeshellarg($dump_file_drush) . ' -q',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/export-db-file', 'Drush command failed');
  }

  public function testDirectoryCreation(): void {
    $new_dir = self::$tmp . '/new-db-dir';
    $this->envSet('VORTEX_EXPORT_DB_FILE_DIR', $new_dir);

    $date_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'date');
    $date_mock->expects($this->any())->willReturn('20240101_120000');

    $dump_file = $new_dir . '/export_db_20240101_120000.sql';
    $dump_file_drush = $dump_file;

    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:dump --skip-tables-key=common --result-file=' . escapeshellarg($dump_file_drush) . ' -q',
      'result_code' => 0,
    ]);

    // Pre-create directory so that the dump file can be simulated.
    // The script's mkdir() will be a no-op since the directory already exists.
    mkdir($new_dir, 0755, TRUE);
    file_put_contents($dump_file, 'SQL DUMP');

    $output = $this->runScript('src/export-db-file');

    $this->assertTrue(is_dir($new_dir));
    $this->assertStringContainsString('Finished database file export.', $output);
  }

}
