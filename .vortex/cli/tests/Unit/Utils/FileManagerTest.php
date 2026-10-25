<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Utils;

use DrevOps\VortexCli\Downloader\Artifact;
use DrevOps\VortexCli\Downloader\Downloader;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Tests\Unit\UnitTestCase;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\FileManager;
use DrevOps\VortexCli\Utils\UpdateRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the FileManager class.
 */
#[CoversClass(FileManager::class)]
class FileManagerTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    static::envUnsetPrefix('VORTEX_CLI');
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
    $dst = self::$sut;
    mkdir($dst . '/.git', 0777, TRUE);

    $config = new Config('/tmp/root', $dst, '/tmp/tmp');
    $fm = new FileManager($config);

    $messages = $fm->prepareDestination();

    $this->assertEmpty($messages);
  }

  public function testPrepareDestinationExistingDirWithoutGit(): void {
    $dst = self::$sut;

    $config = new Config('/tmp/root', $dst, '/tmp/tmp');
    $fm = new FileManager($config);

    $messages = $fm->prepareDestination();

    $this->assertNotEmpty($messages);
    $this->assertDirectoryExists($dst . '/.git');
    $this->assertStringContainsString('Initialising a new Git repository', $messages[0]);
  }

  public function testPrepareDestinationCreatesNewDir(): void {
    $dst = self::$sut . '/new_subdir';

    $config = new Config('/tmp/root', $dst, '/tmp/tmp');
    $fm = new FileManager($config);

    $messages = $fm->prepareDestination();

    $this->assertDirectoryExists($dst);
    $this->assertDirectoryExists($dst . '/.git');

    $has_created_msg = FALSE;
    $has_git_msg = FALSE;
    foreach ($messages as $message) {
      if (str_contains($message, 'Created directory')) {
        $has_created_msg = TRUE;
      }
      if (str_contains($message, 'Initialising a new Git repository')) {
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
    $dst = self::$sut . '/dst_copy';
    mkdir($src, 0777, TRUE);
    mkdir($dst, 0777, TRUE);
    file_put_contents($src . '/test.txt', 'content');

    $config = new Config('/tmp/root', $dst, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertFileExists($dst . '/test.txt');
    $this->assertEquals('content', file_get_contents($dst . '/test.txt'));
  }

  public function testCopyFilesCreatesEnvLocal(): void {
    $src = self::$sut . '/src_envlocal';
    $dst = self::$sut . '/dst_envlocal';
    mkdir($src, 0777, TRUE);
    mkdir($dst, 0777, TRUE);
    file_put_contents($src . '/test.txt', 'content');

    $config = new Config('/tmp/root', $dst, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    // Create the .env.local.example after copy.
    file_put_contents($dst . '/.env.local.example', 'EXAMPLE=1');

    // Re-run to trigger the .env.local creation.
    // Recreate src for the second run.
    mkdir($src, 0777, TRUE);
    file_put_contents($src . '/dummy.txt', 'dummy');
    $fm->copyFiles();

    $this->assertFileExists($dst . '/.env.local');
    $this->assertEquals('EXAMPLE=1', file_get_contents($dst . '/.env.local'));
  }

  public function testCopyFilesSkipsEnvLocalIfExists(): void {
    $src = self::$sut . '/src_envexist';
    $dst = self::$sut . '/dst_envexist';
    mkdir($src, 0777, TRUE);
    mkdir($dst, 0777, TRUE);
    file_put_contents($src . '/test.txt', 'content');
    file_put_contents($dst . '/.env.local', 'EXISTING=1');
    file_put_contents($dst . '/.env.local.example', 'EXAMPLE=1');

    $config = new Config('/tmp/root', $dst, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertEquals('EXISTING=1', file_get_contents($dst . '/.env.local'));
  }

  public function testCopyFilesHandlesEmptySrc(): void {
    $src = self::$sut . '/src_empty';
    $dst = self::$sut . '/dst_empty';
    mkdir($src, 0777, TRUE);
    mkdir($dst, 0777, TRUE);

    $config = new Config('/tmp/root', $dst, $src);
    $fm = new FileManager($config);

    // Should not throw.
    $fm->copyFiles();

    $this->addToAssertionCount(1);
  }

  public function testResetStaging(): void {
    $src = self::$sut . '/src_reset';
    File::dump(File::mkdir($src) . '/stale.txt', 'left behind');

    $fm = new FileManager(new Config('/tmp/root', self::$sut, $src));
    $fm->resetStaging();

    $this->assertDirectoryExists($src);
    $this->assertTrue(File::dirIsEmpty($src), 'A previous run leaves nothing in the staging directory.');
  }

  public function testCopyFilesRemovesUnmodifiedExcludedPaths(): void {
    $src = self::$sut . '/src_excluded';
    $dst = self::$sut . '/dst_excluded';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');
    File::dump(File::mkdir($src . '/.circleci') . '/config.yml', 'version: 2.1');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // A previous install wrote both, unmodified since.
    File::dump(File::mkdir($dst) . '/phpstan.neon', 'parameters: []');
    File::dump(File::mkdir($dst . '/.circleci') . '/config.yml', 'version: 2.1');
    $this->stubPreviousTemplate($fm, $dst, [
      'phpstan.neon' => 'parameters: []',
      '.circleci/config.yml' => 'version: 2.1',
    ]);

    $fm->snapshotTemplate();

    // The current selection drops them from the staged copy.
    File::remove($src . '/phpstan.neon');
    File::remove($src . '/.circleci');

    $fm->copyFiles();

    $this->assertFileDoesNotExist($dst . '/phpstan.neon', 'Unmodified excluded file removed from the destination.');
    $this->assertFileDoesNotExist($dst . '/.circleci/config.yml', 'Unmodified excluded directory contents removed.');
    $this->assertDirectoryDoesNotExist($dst . '/.circleci', 'Directory emptied by the removal is pruned.');
    $this->assertFileExists($dst . '/composer.json', 'Shipped files still copied.');
  }

  public function testCopyFilesRemovesUnmodifiedPathsDroppedByTemplate(): void {
    $src = self::$sut . '/src_dropped';
    $dst = self::$sut . '/dst_dropped';
    File::dump(File::mkdir($src) . '/composer.json', '{}');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // The version the project runs shipped the script; the new one does not.
    File::dump(File::mkdir($dst . '/scripts') . '/legacy.sh', 'echo legacy');
    File::dump($dst . '/scripts/custom.sh', 'echo custom');
    $this->stubPreviousTemplate($fm, $dst, ['scripts/legacy.sh' => 'echo legacy']);

    $fm->snapshotTemplate();
    $fm->copyFiles();

    $this->assertFileDoesNotExist($dst . '/scripts/legacy.sh', 'A file the template stopped shipping is removed.');
    $this->assertFileExists($dst . '/scripts/custom.sh', 'A sibling the project authored is kept.');
  }

  public function testCopyFilesKeepsModifiedExcludedPaths(): void {
    $src = self::$sut . '/src_modified';
    $dst = self::$sut . '/dst_modified';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // The project edited the file after the previous install wrote it.
    File::dump(File::mkdir($dst) . '/phpstan.neon', "parameters:\n  level: 8");
    $this->stubPreviousTemplate($fm, $dst, ['phpstan.neon' => 'parameters: []']);

    $fm->snapshotTemplate();
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($dst . '/phpstan.neon', 'A file the project edited is never removed.');
    $this->assertStringEqualsFile($dst . '/phpstan.neon', "parameters:\n  level: 8", 'The project edit is left untouched.');
  }

  public function testCopyFilesKeepsExcludedPathsWithoutRecordedHash(): void {
    $src = self::$sut . '/src_unverifiable';
    $dst = self::$sut . '/dst_unverifiable';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    // No previous version, so ownership cannot be established.
    File::dump(File::mkdir($dst) . '/phpstan.neon', 'parameters: []');
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($dst . '/phpstan.neon', 'Without a recorded hash the file is left alone.');
  }

  /**
   * @param string|null $readme
   *   Content of the project README, or NULL to write none.
   * @param bool $download_fails
   *   Whether the download of the previous version fails.
   */
  #[DataProvider('dataProviderSnapshotPreviousTemplateWithoutUsableVersion')]
  public function testSnapshotPreviousTemplateWithoutUsableVersion(?string $readme, bool $download_fails): void {
    $src = self::$sut . '/src_no_previous';
    $dst = self::$sut . '/dst_no_previous';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');
    File::dump(File::mkdir($dst) . '/phpstan.neon', 'parameters: []');

    if ($readme !== NULL) {
      File::dump($dst . '/README.md', $readme);
    }

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createStub(RepositoryDownloader::class);
    $downloader->method('download')->willReturnCallback(function (Artifact $artifact, ?string $dir = NULL) use ($download_fails): string {
      if ($download_fails) {
        throw new \RuntimeException('Unable to download.');
      }

      File::dump($dir . '/phpstan.neon', 'parameters: []');

      return $artifact->getRef();
    });

    $fm->snapshotPreviousTemplate($downloader, Artifact::create('https://github.com/drevops/vortex.git', '2.1.0'));
    $fm->snapshotTemplate();
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($dst . '/phpstan.neon', 'Without the previous version the file is left alone.');
    $this->assertDirectoryDoesNotExist($src . '-previous', 'The previous version is never left behind.');
  }

  /**
   * Data provider for testSnapshotPreviousTemplateWithoutUsableVersion().
   *
   * @return \Iterator<string, array{(string | null), bool}>
   *   Test data.
   */
  public static function dataProviderSnapshotPreviousTemplateWithoutUsableVersion(): \Iterator {
    yield 'no badge' => ['# Project', FALSE];
    yield 'download failed' => ['[![Vortex](https://img.shields.io/badge/Vortex-2.0.0-65ACBC.svg)](https://github.com/drevops/vortex)', TRUE];
  }

  public function testCopyFilesRemovesExcludedPathsMatchedOnlyAfterRendering(): void {
    $src = self::$sut . '/src_rendered';
    $dst = self::$sut . '/dst_rendered';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/rector.php', 'paths: your_site');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // The project holds the rendered content, which the download never has.
    File::dump(File::mkdir($dst) . '/rector.php', 'paths: star_wars');
    $this->stubPreviousTemplate($fm, $dst, ['rector.php' => 'paths: your_site'], function (string $dir, string $ref): void {
      $this->assertSame('2.0.0', $ref, 'The render receives the reference the project runs.');
      File::dump($dir . '/rector.php', 'paths: star_wars');
    });

    $fm->snapshotTemplate();
    File::remove($src . '/rector.php');

    $fm->copyFiles();

    $this->assertFileDoesNotExist($dst . '/rector.php', 'Rendering resolves tokens that the download itself does not match.');
    $this->assertDirectoryDoesNotExist($src . '-previous', 'The rendered previous version is removed after the copy.');
  }

  public function testCopyFilesRemovesExcludedPathsDeselectedByThisRun(): void {
    $src = self::$sut . '/src_deselected';
    $dst = self::$sut . '/dst_deselected';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/jest.config.js', 'module.exports = {};');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    File::dump(File::mkdir($dst) . '/jest.config.js', 'module.exports = {};');

    // Discovery answers describe the project, which still has the tool, so
    // the render keeps the file even though this run deselects it.
    $this->stubPreviousTemplate($fm, $dst, ['jest.config.js' => 'module.exports = {};']);

    $fm->snapshotTemplate();
    File::remove($src . '/jest.config.js');

    $fm->copyFiles();

    $this->assertFileDoesNotExist($dst . '/jest.config.js', 'A tool the project has is still removable when this run deselects it.');
  }

  public function testCopyFilesRecordsReplacedProjectChanges(): void {
    $src = self::$sut . '/src_registry';
    $dst = self::$sut . '/dst_registry';
    File::dump(File::mkdir($src) . '/phpstan.neon', "parameters:\n  level: 9\n");

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $config->set(Config::VERSION, '2.1.0', TRUE);
    $fm = new FileManager($config);

    // The project edited the file the previous version installed.
    File::dump(File::mkdir($dst) . '/phpstan.neon', "parameters:\n  level: 8\n");
    $this->stubPreviousTemplate($fm, $dst, ['phpstan.neon' => "parameters:\n  level: 5\n"]);

    $fm->snapshotTemplate();
    $fm->copyFiles();

    $registry = $dst . '/' . UpdateRegistry::FILE;

    $this->assertEquals($registry, $fm->getRegistryFile());
    $this->assertStringEqualsFile($dst . '/phpstan.neon', "parameters:\n  level: 9\n", 'The update still replaces the project file.');
    $this->assertFileContainsString($registry, '## 2.0.0 to 2.1.0');
    $this->assertFileContainsString($registry, '### phpstan.neon');
    $this->assertFileContainsString($registry, '-  level: 5');
    $this->assertFileContainsString($registry, '+  level: 8');
    $this->assertFileContainsString($registry, '+  level: 9');
  }

  public function testCopyFilesRecordsNothingWithoutProjectChanges(): void {
    $src = self::$sut . '/src_no_registry';
    $dst = self::$sut . '/dst_no_registry';
    File::dump(File::mkdir($src) . '/phpstan.neon', "parameters:\n  level: 9\n");
    File::dump($src . '/rector.php', 'paths: web');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // One file as the previous version installed it, one already updated.
    File::dump(File::mkdir($dst) . '/phpstan.neon', "parameters:\n  level: 5\n");
    File::dump($dst . '/rector.php', 'paths: web');
    $this->stubPreviousTemplate($fm, $dst, ['phpstan.neon' => "parameters:\n  level: 5\n", 'rector.php' => 'paths: docroot']);

    $fm->snapshotTemplate();
    $fm->copyFiles();

    $this->assertNull($fm->getRegistryFile());
    $this->assertFileDoesNotExist($dst . '/' . UpdateRegistry::FILE, 'An untouched file leaves nothing to reconcile.');
  }

  public function testCopyFilesKeepsPathsTheTemplateNeverShipped(): void {
    $src = self::$sut . '/src_unknown';
    $dst = self::$sut . '/dst_unknown';
    File::dump(File::mkdir($src) . '/composer.json', '{}');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    File::dump(File::mkdir($dst) . '/phpstan.neon', 'project owned');
    File::dump(File::mkdir($dst . '/web/modules/custom/mymodule') . '/mymodule.info.yml', 'name: My module');

    $fm->copyFiles();

    $this->assertFileExists($dst . '/phpstan.neon', 'A path the template never shipped is left alone.');
    $this->assertFileExists($dst . '/web/modules/custom/mymodule/mymodule.info.yml', 'Project-authored content is left alone.');
  }

  public function testCopyFilesKeepsExcludedPathsForNonVortexProject(): void {
    $src = self::$sut . '/src_fresh';
    $dst = self::$sut . '/dst_fresh';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');

    $config = new Config('/tmp/root', $dst, $src);
    $fm = new FileManager($config);

    File::dump(File::mkdir($dst) . '/phpstan.neon', 'parameters: []');
    $this->stubPreviousTemplate($fm, $dst, ['phpstan.neon' => 'parameters: []']);

    $fm->snapshotTemplate();
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($dst . '/phpstan.neon', 'A destination that is not a Vortex project is never pruned.');
  }

  public function testCopyFilesKeepsHarnessPaths(): void {
    $src = self::$sut . '/src_harness';
    $dst = self::$sut . '/dst_harness';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump(File::mkdir($src . '/.vortex') . '/CLAUDE.md', 'harness');

    $config = new Config('/tmp/root', $dst, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    File::dump(File::mkdir($dst . '/.vortex') . '/CLAUDE.md', 'harness');
    $this->stubPreviousTemplate($fm, $dst, ['.vortex/CLAUDE.md' => 'harness']);

    $fm->snapshotTemplate();
    File::remove($src . '/.vortex');

    $fm->copyFiles();

    $this->assertFileExists($dst . '/.vortex/CLAUDE.md', "The harness never ships, so a matching path is the project's own.");
  }

  public function testCopyFilesRemovesObsoleteScriptsVortex(): void {
    // Simulate an upgrade from a Vortex version that shipped scripts at
    // 'scripts/vortex/' before they were extracted into the
    // 'drevops/vortex-tooling' Composer package. The legacy directory must
    // be removed from the destination after the copy.
    $src = self::$sut . '/src_obsolete';
    $dst = self::$sut . '/dst_obsolete';
    mkdir($src, 0777, TRUE);
    mkdir($dst . '/scripts/vortex', 0777, TRUE);
    file_put_contents($src . '/test.txt', 'new');
    file_put_contents($dst . '/scripts/vortex/legacy.sh', 'legacy');
    file_put_contents($dst . '/scripts/keep.sh', 'custom');

    $config = new Config('/tmp/root', $dst, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertDirectoryDoesNotExist($dst . '/scripts/vortex', 'Legacy scripts/vortex/ directory removed after copy.');
    $this->assertFileExists($dst . '/scripts/keep.sh', 'Sibling custom scripts/ entries preserved.');
    $this->assertFileExists($dst . '/test.txt', 'New files copied from source.');
  }

  public function testRemoveObsoletePathsSilentOnMissing(): void {
    $dst = self::$sut . '/dst_no_obsolete';
    mkdir($dst, 0777, TRUE);

    $config = new Config('/tmp/root', $dst, '/tmp/tmp');
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
    $dst = self::$sut;
    file_put_contents($dst . '/.env', '');

    $config = new Config('/tmp/root', $dst, '/tmp/tmp');
    $config->set(Config::IS_DEMO, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $result = $fm->prepareDemo($downloader);

    $this->assertIsString($result);
    $this->assertStringContainsString('No database fetch URL provided', $result);
  }

  public function testPrepareDemoExistingDatabaseFile(): void {
    $dst = self::$sut;
    $data_dir = $dst . '/.data';
    mkdir($data_dir, 0777, TRUE);
    file_put_contents($data_dir . '/db.sql', 'existing');
    file_put_contents($dst . '/.env', "VORTEX_FETCH_DB_URL=https://example.com/db.sql\nVORTEX_DB_DIR=./.data\nVORTEX_DB_FILE=db.sql\n");

    $config = new Config('/tmp/root', $dst, '/tmp/tmp');
    $config->set(Config::IS_DEMO, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $result = $fm->prepareDemo($downloader);

    $this->assertIsString($result);
    $this->assertStringContainsString('already exists', $result);
  }

  public function testPrepareDemoFetchesDatabase(): void {
    $dst = self::$sut;
    file_put_contents($dst . '/.env', "VORTEX_FETCH_DB_URL=https://example.com/db.sql\nVORTEX_DB_DIR=./.data\nVORTEX_DB_FILE=db.sql\n");

    $config = new Config('/tmp/root', $dst, '/tmp/tmp');
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
    $dst = self::$sut;
    file_put_contents($dst . '/.env', "VORTEX_FETCH_DB_URL=https://example.com/db.sql\nVORTEX_DB_DIR=./.data\nVORTEX_DB_FILE=db.sql\n");

    $config = new Config('/tmp/root', $dst, '/tmp/tmp');
    $config->set(Config::IS_DEMO, TRUE);
    $fm = new FileManager($config);

    $downloader = $this->createMock(Downloader::class);
    $result = $fm->prepareDemo($downloader);

    $this->assertIsArray($result);
    $this->assertDirectoryExists($dst . '/.data');

    $has_created_msg = FALSE;
    foreach ($result as $msg) {
      if (str_contains((string) $msg, 'Created data directory')) {
        $has_created_msg = TRUE;
      }
    }
    $this->assertTrue($has_created_msg);
  }

  /**
   * Snapshot a stubbed download of the version the project runs.
   *
   * @param \DrevOps\VortexCli\Utils\FileManager $fm
   *   The file manager to snapshot into.
   * @param string $dst
   *   The project directory.
   * @param array<string, string> $files
   *   Content the previous version installed, keyed by relative path.
   * @param callable|null $render
   *   Callback turning the download into installable content.
   */
  protected function stubPreviousTemplate(FileManager $fm, string $dst, array $files, ?callable $render = NULL): void {
    File::dump($dst . '/README.md', '[![Vortex](https://img.shields.io/badge/Vortex-2.0.0-65ACBC.svg)](https://github.com/drevops/vortex)');

    $downloader = $this->createStub(RepositoryDownloader::class);
    $downloader->method('download')->willReturnCallback(function (Artifact $artifact, ?string $dir = NULL) use ($files): string {
      foreach ($files as $path => $contents) {
        File::dump($dir . '/' . $path, $contents);
      }

      return $artifact->getRef();
    });

    $fm->snapshotPreviousTemplate($downloader, Artifact::create('https://github.com/drevops/vortex.git', '2.0.0'), $render);
  }

}
