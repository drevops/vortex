<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use DrevOps\VortexInstaller\Downloader\Downloader;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
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

  public function testCopyFilesRemovesUnmodifiedExcludedPaths(): void {
    $src = self::$sut . '/src_excluded';
    $destination = self::$sut . '/dst_excluded';
    file_put_contents(File::mkdir($src) . '/composer.json', '{}');
    file_put_contents($src . '/phpstan.neon', 'parameters: []');
    file_put_contents(File::mkdir($src . '/.circleci') . '/config.yml', 'version: 2.1');

    $config = new Config('/tmp/root', $destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // A previous install wrote both, unmodified since.
    file_put_contents(File::mkdir($destination) . '/phpstan.neon', 'parameters: []');
    file_put_contents(File::mkdir($destination . '/.circleci') . '/config.yml', 'version: 2.1');
    $this->stubManifest($destination, [
      'phpstan.neon' => 'parameters: []',
      '.circleci/config.yml' => 'version: 2.1',
    ]);

    $fm->snapshotTemplate();

    // The current selection drops them from the staged copy.
    File::remove($src . '/phpstan.neon');
    File::remove($src . '/.circleci');

    $fm->copyFiles();

    $this->assertFileDoesNotExist($destination . '/phpstan.neon', 'Unmodified excluded file removed from the destination.');
    $this->assertFileDoesNotExist($destination . '/.circleci/config.yml', 'Unmodified excluded directory contents removed.');
    $this->assertDirectoryDoesNotExist($destination . '/.circleci', 'Directory emptied by the removal is pruned.');
    $this->assertFileExists($destination . '/composer.json', 'Shipped files still copied.');
  }

  public function testCopyFilesKeepsModifiedExcludedPaths(): void {
    $src = self::$sut . '/src_modified';
    $destination = self::$sut . '/dst_modified';
    file_put_contents(File::mkdir($src) . '/composer.json', '{}');
    file_put_contents($src . '/phpstan.neon', 'parameters: []');

    $config = new Config('/tmp/root', $destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // The project edited the file after the previous install wrote it.
    file_put_contents(File::mkdir($destination) . '/phpstan.neon', "parameters:\n  level: 8");
    $this->stubManifest($destination, ['phpstan.neon' => 'parameters: []']);

    $fm->snapshotTemplate();
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/phpstan.neon', 'A file the project edited is never removed.');
    $this->assertStringEqualsFile($destination . '/phpstan.neon', "parameters:\n  level: 8", 'The project edit is left untouched.');
  }

  public function testCopyFilesKeepsExcludedPathsWithoutRecordedHash(): void {
    $src = self::$sut . '/src_unverifiable';
    $destination = self::$sut . '/dst_unverifiable';
    file_put_contents(File::mkdir($src) . '/composer.json', '{}');
    file_put_contents($src . '/phpstan.neon', 'parameters: []');

    $config = new Config('/tmp/root', $destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    // No manifest and no previous version, so ownership cannot be established.
    file_put_contents(File::mkdir($destination) . '/phpstan.neon', 'parameters: []');
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/phpstan.neon', 'Without a recorded hash the file is left alone.');
  }

  public function testCopyFilesWritesTheManifest(): void {
    $src = self::$sut . '/src_manifest';
    $destination = self::$sut . '/dst_manifest';
    file_put_contents(File::mkdir($src) . '/composer.json', '{}');
    file_put_contents(File::mkdir($src . '/scripts') . '/provision.sh', 'echo 1');

    $config = new Config('/tmp/root', $destination, $src);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    $fm->copyFiles();

    $manifest = json_decode((string) file_get_contents($destination . '/' . FileManager::MANIFEST_FILE), TRUE);

    $this->assertIsArray($manifest);
    $this->assertArrayHasKey('scripts/provision.sh', $manifest, 'Manifest records every shipped path.');
    $this->assertEquals(hash('sha256', 'echo 1'), $manifest['scripts/provision.sh'], 'Manifest records the content that was written.');
  }

  public function testCopyFilesKeepsPathsTheTemplateNeverShipped(): void {
    $src = self::$sut . '/src_unknown';
    $destination = self::$sut . '/dst_unknown';
    file_put_contents(File::mkdir($src) . '/composer.json', '{}');

    $config = new Config('/tmp/root', $destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    file_put_contents(File::mkdir($destination) . '/phpstan.neon', 'project owned');
    file_put_contents(File::mkdir($destination . '/web/modules/custom/mymodule') . '/mymodule.info.yml', 'name: My module');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/phpstan.neon', 'A path the template never shipped is left alone.');
    $this->assertFileExists($destination . '/web/modules/custom/mymodule/mymodule.info.yml', 'Project-authored content is left alone.');
  }

  public function testCopyFilesKeepsExcludedPathsForNonVortexProject(): void {
    $src = self::$sut . '/src_fresh';
    $destination = self::$sut . '/dst_fresh';
    file_put_contents(File::mkdir($src) . '/composer.json', '{}');
    file_put_contents($src . '/phpstan.neon', 'parameters: []');

    $config = new Config('/tmp/root', $destination, $src);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    file_put_contents(File::mkdir($destination) . '/phpstan.neon', 'project owned');
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/phpstan.neon', 'A destination that is not a Vortex project is never pruned.');
  }

  public function testCopyFilesKeepsHarnessPaths(): void {
    $src = self::$sut . '/src_harness';
    $destination = self::$sut . '/dst_harness';
    file_put_contents(File::mkdir($src) . '/composer.json', '{}');
    file_put_contents(File::mkdir($src . '/.vortex') . '/CLAUDE.md', 'harness');

    $config = new Config('/tmp/root', $destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    file_put_contents(File::mkdir($destination . '/.vortex') . '/CLAUDE.md', 'project owned');
    File::remove($src . '/.vortex');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/.vortex/CLAUDE.md', "The harness never ships, so a matching path is the project's own.");
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
   * Write a manifest recording what a previous install wrote.
   *
   * @param string $destination
   *   The project directory.
   * @param array<string, string> $files
   *   Content the previous install wrote, keyed by relative path.
   */
  protected function stubManifest(string $destination, array $files): void {
    $hashes = array_map(fn(string $contents): string => hash('sha256', $contents), $files);

    File::dump($destination . '/' . FileManager::MANIFEST_FILE, (string) json_encode($hashes, JSON_PRETTY_PRINT));
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
