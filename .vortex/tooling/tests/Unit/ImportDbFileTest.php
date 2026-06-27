<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class ImportDbFileTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_IMPORT_DB_FILE_DIR', self::$tmp);
    $this->envUnset('VORTEX_DB_DIR');
    $this->envSet('VORTEX_IMPORT_DB_FILE', 'db.sql');
    $this->envUnset('VORTEX_DB_FILE');

    // Reset argv to prevent PHPUnit arguments from leaking into the script.
    $GLOBALS['argv'] = ['import-db-file'];
  }

  public function testImportFromArgument(): void {
    $dump_file = self::$tmp . '/db_custom.sql';
    file_put_contents($dump_file, 'SQL DUMP');

    $GLOBALS['argv'] = ['import-db-file', $dump_file];

    $this->mockImportSequence($dump_file);

    $output = $this->runScript('src/import-db-file');

    $this->assertStringContainsString('Started database file import.', $output);
    $this->assertStringContainsString('Imported database from the dump file.', $output);
    $this->assertStringContainsString('Finished database file import.', $output);
  }

  public function testImportFromDefaultLocation(): void {
    $dump_file = self::$tmp . '/db.sql';
    file_put_contents($dump_file, 'SQL DUMP');

    $this->mockImportSequence($dump_file);

    $output = $this->runScript('src/import-db-file');

    $this->assertStringContainsString('Imported database from the dump file.', $output);
    $this->assertStringContainsString('Finished database file import.', $output);
  }

  public function testImportFromDbDirFallback(): void {
    $this->envUnset('VORTEX_IMPORT_DB_FILE_DIR');
    $this->envSet('VORTEX_DB_DIR', self::$tmp);
    $this->envUnset('VORTEX_IMPORT_DB_FILE');
    $this->envSet('VORTEX_DB_FILE', 'db.sql');

    $dump_file = self::$tmp . '/db.sql';
    file_put_contents($dump_file, 'SQL DUMP');

    $this->mockImportSequence($dump_file);

    $output = $this->runScript('src/import-db-file');

    $this->assertStringContainsString('Imported database from the dump file.', $output);
  }

  public function testFileMissing(): void {
    $dump_file = self::$tmp . '/missing.sql';

    $GLOBALS['argv'] = ['import-db-file', $dump_file];

    $this->runScriptError('src/import-db-file', 'Unable to import database from file.');
  }

  public function testFileNotReadable(): void {
    if (posix_getuid() === 0) {
      $this->markTestSkipped('Root bypasses file read permissions.');
    }

    $dump_file = self::$tmp . '/db.sql';
    file_put_contents($dump_file, 'SQL DUMP');
    chmod($dump_file, 0000);

    $GLOBALS['argv'] = ['import-db-file', $dump_file];

    try {
      $this->runScriptError('src/import-db-file', 'is not readable.');
    }
    finally {
      chmod($dump_file, 0644);
    }
  }

  public function testImportFails(): void {
    $dump_file = self::$tmp . '/db.sql';
    file_put_contents($dump_file, 'SQL DUMP');

    $GLOBALS['argv'] = ['import-db-file', $dump_file];

    // Drush sql:drop.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:drop',
      'result_code' => 0,
    ]);

    // Drush sql:connect.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:connect',
      'output' => 'mysql -u root -p test_db',
      'result_code' => 0,
    ]);

    // SQL import fails.
    $this->mockPassthru([
      'cmd' => 'mysql -u root -p test_db <' . escapeshellarg($dump_file),
      'result_code' => 1,
    ]);

    $this->runScriptError('src/import-db-file', 'Failed to import database from dump file.');
  }

  protected function mockImportSequence(string $dump_file): void {
    // Drush sql:drop.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:drop',
      'result_code' => 0,
    ]);

    // Drush sql:connect.
    $this->mockPassthru([
      'cmd' => './vendor/bin/drush -y sql:connect',
      'output' => 'mysql -u root -p test_db',
      'result_code' => 0,
    ]);

    // SQL import via piped command.
    $this->mockPassthru([
      'cmd' => 'mysql -u root -p test_db <' . escapeshellarg($dump_file),
      'result_code' => 0,
    ]);
  }

}
