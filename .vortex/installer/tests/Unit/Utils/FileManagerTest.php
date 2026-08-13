<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use DrevOps\VortexInstaller\Downloader\Downloader;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\FileManager;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the FileManager class.
 */
#[CoversClass(FileManager::class)]
class FileManagerTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    static::envUnsetPrefix('VORTEX_INSTALLER');
    static::envUnsetPrefix('VORTEX_FETCH');
    static::envUnsetPrefix('VORTEX_DB');
  }

  public function testConstructor(): void {
    $config = new Config('/tmp/root', self::$sut, '/tmp/tmp');
    $fm = new FileManager($config);

    $this->assertInstanceOf(FileManager::class, $fm);
  }

  /**
   * Tests for prepareDestination().
   */
  public function testPrepareDestinationExistingDirWithGit(): void {
    $destination = self::$sut;
    mkdir($destination . '/.git', 0777, TRUE);

    $config = new Config('/tmp/root', $destination, '/tmp/tmp');
    $fm = new FileManager($config);

    $messages = $fm->prepareDestination();

    $this->assertEmpty($messages);
  }

  public function testPrepareDestinationExistingDirWithoutGit(): void {
    $destination = self::$sut;

    $config = new Config('/tmp/root', $destination, '/tmp/tmp');
    $fm = new FileManager($config);

    $messages = $fm->prepareDestination();

    $this->assertNotEmpty($messages);
    $this->assertDirectoryExists($destination . '/.git');
    $this->assertStringContainsString('Initializing a new Git repository', $messages[0]);
  }

  public function testPrepareDestinationCreatesNewDir(): void {
    $destination = self::$sut . '/new_subdir';

    $config = new Config('/tmp/root', $destination, '/tmp/tmp');
    $fm = new FileManager($config);

    $messages = $fm->prepareDestination();

    $this->assertDirectoryExists($destination);
    $this->assertDirectoryExists($destination . '/.git');

    $has_created_msg = FALSE;
    $has_git_msg = FALSE;
    foreach ($messages as $message) {
      if (str_contains($message, 'Created directory')) {
        $has_created_msg = TRUE;
      }
      if (str_contains($message, 'Initializing a new Git repository')) {
        $has_git_msg = TRUE;
      }
    }
    $this->assertTrue($has_created_msg);
    $this->assertTrue($has_git_msg);
  }

  /**
   * Tests for copyFiles().
   */
  public function testCopyFilesCopiesToDestination(): void {
    $src = self::$sut . '/src_copy';
    $destination = self::$sut . '/dst_copy';
    mkdir($src, 0777, TRUE);
    mkdir($destination, 0777, TRUE);
    file_put_contents($src . '/test.txt', 'content');

    $config = new Config('/tmp/root', $destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertFileExists($destination . '/test.txt');
    $this->assertEquals('content', file_get_contents($destination . '/test.txt'));
  }

  public function testCopyFilesCreatesEnvLocal(): void {
    $src = self::$sut . '/src_envlocal';
    $destination = self::$sut . '/dst_envlocal';
    mkdir($src, 0777, TRUE);
    mkdir($destination, 0777, TRUE);
    file_put_contents($src . '/test.txt', 'content');

    $config = new Config('/tmp/root', $destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    // Create the .env.local.example after copy.
    file_put_contents($destination . '/.env.local.example', 'EXAMPLE=1');

    // Re-run to trigger the .env.local creation.
    // Recreate src for the second run.
    mkdir($src, 0777, TRUE);
    file_put_contents($src . '/dummy.txt', 'dummy');
    $fm->copyFiles();

    $this->assertFileExists($destination . '/.env.local');
    $this->assertEquals('EXAMPLE=1', file_get_contents($destination . '/.env.local'));
  }

  public function testCopyFilesSkipsEnvLocalIfExists(): void {
    $src = self::$sut . '/src_envexist';
    $destination = self::$sut . '/dst_envexist';
    mkdir($src, 0777, TRUE);
    mkdir($destination, 0777, TRUE);
    file_put_contents($src . '/test.txt', 'content');
    file_put_contents($destination . '/.env.local', 'EXISTING=1');
    file_put_contents($destination . '/.env.local.example', 'EXAMPLE=1');

    $config = new Config('/tmp/root', $destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertEquals('EXISTING=1', file_get_contents($destination . '/.env.local'));
  }

  public function testCopyFilesHandlesEmptySrc(): void {
    $src = self::$sut . '/src_empty';
    $destination = self::$sut . '/dst_empty';
    mkdir($src, 0777, TRUE);
    mkdir($destination, 0777, TRUE);

    $config = new Config('/tmp/root', $destination, $src);
    $fm = new FileManager($config);

    // Should not throw.
    $fm->copyFiles();

    $this->addToAssertionCount(1);
  }

  public function testCopyFilesLeavesDestinationOnlyFilesInPlace(): void {
    // The copy is an overlay: a path the source no longer carries is not
    // deleted from the destination. Handlers remove a deselected feature's
    // files from the source only, so an existing project keeps its copy.
    $src = self::$sut . '/src_overlay';
    $destination = self::$sut . '/dst_overlay';
    mkdir($src, 0777, TRUE);
    mkdir($destination . '/.circleci', 0777, TRUE);
    file_put_contents($src . '/package.json', '{"devDependencies":{}}');
    file_put_contents($destination . '/package.json', '{"devDependencies":{"jest":"*"}}');
    file_put_contents($destination . '/jest.config.js', 'module.exports = {};');
    file_put_contents($destination . '/.circleci/config.yml', 'version: 2.1');

    $config = new Config('/tmp/root', $destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertFileExists($destination . '/jest.config.js', 'Deselected tool config file remains in the destination.');
    $this->assertFileExists($destination . '/.circleci/config.yml', 'Deselected CI provider config remains in the destination.');
    $this->assertEquals('{"devDependencies":{}}', file_get_contents($destination . '/package.json'), 'Shipped files are overwritten by the source version.');
  }

  public function testCopyFilesRemovesObsoleteScriptsVortex(): void {
    // Simulate an upgrade from a Vortex version that shipped scripts at
    // 'scripts/vortex/' before they were extracted into the
    // 'drevops/vortex-tooling' Composer package. The legacy directory must
    // be removed from the destination after the copy.
    $src = self::$sut . '/src_obsolete';
    $destination = self::$sut . '/dst_obsolete';
    mkdir($src, 0777, TRUE);
    mkdir($destination . '/scripts/vortex', 0777, TRUE);
    file_put_contents($src . '/test.txt', 'new');
    file_put_contents($destination . '/scripts/vortex/legacy.sh', 'legacy');
    file_put_contents($destination . '/scripts/keep.sh', 'custom');

    $config = new Config('/tmp/root', $destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertDirectoryDoesNotExist($destination . '/scripts/vortex', 'Legacy scripts/vortex/ directory removed after copy.');
    $this->assertFileExists($destination . '/scripts/keep.sh', 'Sibling custom scripts/ entries preserved.');
    $this->assertFileExists($destination . '/test.txt', 'New files copied from source.');
  }

  public function testRemoveObsoletePathsSilentOnMissing(): void {
    $destination = self::$sut . '/dst_no_obsolete';
    mkdir($destination, 0777, TRUE);

    $config = new Config('/tmp/root', $destination, '/tmp/tmp');
    $fm = new FileManager($config);

    // Should not throw when there is nothing to remove.
    $fm->removeObsoletePaths();

    $this->addToAssertionCount(1);
  }

  /**
   * Tests for prepareDemo().
   */
  public function testPrepareDemoNotDemoMode(): void {
    $config = new Config('/tmp/root', self::$sut, '/tmp/tmp');
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $result = $fm->prepareDemo($downloader);

    $this->assertEquals('Not a demo mode.', $result);
  }

  public function testPrepareDemoWithFetchSkip(): void {
    $config = new Config('/tmp/root', self::$sut, '/tmp/tmp');
    $config->set(Config::IS_DEMO, TRUE);
    $config->set(Config::IS_DEMO_DB_FETCH_SKIP, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $result = $fm->prepareDemo($downloader);

    $this->assertIsString($result);
    $this->assertStringContainsString('Skipping demo database fetch', $result);
  }

  public function testPrepareDemoNoUrl(): void {
    $destination = self::$sut;
    file_put_contents($destination . '/.env', '');

    $config = new Config('/tmp/root', $destination, '/tmp/tmp');
    $config->set(Config::IS_DEMO, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $result = $fm->prepareDemo($downloader);

    $this->assertIsString($result);
    $this->assertStringContainsString('No database fetch URL provided', $result);
  }

  public function testPrepareDemoExistingDatabaseFile(): void {
    $destination = self::$sut;
    $data_dir = $destination . '/.data';
    mkdir($data_dir, 0777, TRUE);
    file_put_contents($data_dir . '/db.sql', 'existing');
    file_put_contents($destination . '/.env', "VORTEX_FETCH_DB_URL=https://example.com/db.sql\nVORTEX_DB_DIR=./.data\nVORTEX_DB_FILE=db.sql\n");

    $config = new Config('/tmp/root', $destination, '/tmp/tmp');
    $config->set(Config::IS_DEMO, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $result = $fm->prepareDemo($downloader);

    $this->assertIsString($result);
    $this->assertStringContainsString('already exists', $result);
  }

  public function testPrepareDemoFetchesDatabase(): void {
    $destination = self::$sut;
    file_put_contents($destination . '/.env', "VORTEX_FETCH_DB_URL=https://example.com/db.sql\nVORTEX_DB_DIR=./.data\nVORTEX_DB_FILE=db.sql\n");

    $config = new Config('/tmp/root', $destination, '/tmp/tmp');
    $config->set(Config::IS_DEMO, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $downloader->expects($this->once())
      ->method('download')
      ->with('https://example.com/db.sql', $this->stringContains('db.sql'));

    $result = $fm->prepareDemo($downloader);

    $this->assertIsArray($result);
    $this->assertNotEmpty($result);

    $has_download_msg = FALSE;
    foreach ($result as $msg) {
      if (str_contains((string) $msg, 'Fetched demo database')) {
        $has_download_msg = TRUE;
      }
    }
    $this->assertTrue($has_download_msg);
  }

  public function testPrepareDemoCreatesDataDir(): void {
    $destination = self::$sut;
    file_put_contents($destination . '/.env', "VORTEX_FETCH_DB_URL=https://example.com/db.sql\nVORTEX_DB_DIR=./.data\nVORTEX_DB_FILE=db.sql\n");

    $config = new Config('/tmp/root', $destination, '/tmp/tmp');
    $config->set(Config::IS_DEMO, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $result = $fm->prepareDemo($downloader);

    $this->assertIsArray($result);
    $this->assertDirectoryExists($destination . '/.data');

    $has_created_msg = FALSE;
    foreach ($result as $msg) {
      if (str_contains((string) $msg, 'Created data directory')) {
        $has_created_msg = TRUE;
      }
    }
    $this->assertTrue($has_created_msg);
  }

}
