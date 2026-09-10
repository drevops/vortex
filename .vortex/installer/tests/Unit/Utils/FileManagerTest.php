<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Utils;

use DrevOps\VortexInstaller\Downloader\Artifact;
use DrevOps\VortexInstaller\Downloader\Downloader;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\FileManager;
use DrevOps\VortexInstaller\Utils\UpdateRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(FileManager::class)]
class FileManagerTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    static::envUnsetPrefix('VORTEX_INSTALLER');
    static::envUnsetPrefix('VORTEX_FETCH');
    static::envUnsetPrefix('VORTEX_DB');
  }

  /**
   * Create a config for a destination, staging from a source when given.
   */
  protected function createConfig(string $destination, ?string $src = NULL): Config {
    return new Config(static::$tmp . '/root', $destination, $src ?? static::$tmp . '/staged');
  }

  public function testConstructor(): void {
    $config = $this->createConfig(self::$sut);
    $fm = new FileManager($config);

    $this->assertInstanceOf(FileManager::class, $fm);
  }

  /**
   * @param string $subdir
   *   Path appended to the test directory to form the destination.
   * @param bool $with_git
   *   Create a repository in the destination before preparing it.
   * @param array<int, string> $expected_messages
   *   Substrings every returned message set must contain.
   */
  #[DataProvider('dataProviderPrepareDestination')]
  public function testPrepareDestination(string $subdir, bool $with_git, array $expected_messages): void {
    $destination = self::$sut . $subdir;

    if ($with_git) {
      File::mkdir($destination . '/.git');
    }

    $fm = new FileManager($this->createConfig($destination));

    $messages = $fm->prepareDestination();

    $this->assertDirectoryExists($destination);
    $this->assertDirectoryExists($destination . '/.git');
    $this->assertCount(count($expected_messages), $messages);

    foreach ($expected_messages as $index => $expected_message) {
      $this->assertStringContainsString($expected_message, $messages[$index]);
    }
  }

  public static function dataProviderPrepareDestination(): \Iterator {
    yield 'existing directory with a repository' => ['', TRUE, []];
    yield 'existing directory without a repository' => ['', FALSE, ['Initializing a new Git repository']];
    yield 'directory created by the install' => ['/new_subdir', FALSE, ['Created directory', 'Initializing a new Git repository']];
  }

  public function testCopyFilesCopiesToDestination(): void {
    $src = self::$sut . '/src_copy';
    $destination = self::$sut . '/dst_copy';
    File::mkdir($src);
    File::mkdir($destination);
    File::dump($src . '/test.txt', 'content');

    $config = $this->createConfig($destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertFileExists($destination . '/test.txt');
    $this->assertEquals('content', File::read($destination . '/test.txt'));
  }

  public function testCopyFilesCreatesEnvLocal(): void {
    $src = self::$sut . '/src_envlocal';
    $destination = self::$sut . '/dst_envlocal';
    File::mkdir($src);
    File::mkdir($destination);
    File::dump($src . '/test.txt', 'content');

    $config = $this->createConfig($destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    // Create the .env.local.example after copy.
    File::dump($destination . '/.env.local.example', 'EXAMPLE=1');

    // Re-run to trigger the .env.local creation.
    // Recreate src for the second run.
    File::mkdir($src);
    File::dump($src . '/dummy.txt', 'dummy');
    $fm->copyFiles();

    $this->assertFileExists($destination . '/.env.local');
    $this->assertEquals('EXAMPLE=1', File::read($destination . '/.env.local'));
  }

  public function testCopyFilesSkipsEnvLocalIfExists(): void {
    $src = self::$sut . '/src_envexist';
    $destination = self::$sut . '/dst_envexist';
    File::mkdir($src);
    File::mkdir($destination);
    File::dump($src . '/test.txt', 'content');
    File::dump($destination . '/.env.local', 'EXISTING=1');
    File::dump($destination . '/.env.local.example', 'EXAMPLE=1');

    $config = $this->createConfig($destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertEquals('EXISTING=1', File::read($destination . '/.env.local'));
  }

  public function testCopyFilesHandlesEmptySrc(): void {
    $src = self::$sut . '/src_empty';
    $destination = self::$sut . '/dst_empty';
    File::mkdir($src);
    File::mkdir($destination);

    $config = $this->createConfig($destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->addToAssertionCount(1);
  }

  public function testCopyFilesRemovesUnmodifiedExcludedPaths(): void {
    $src = self::$sut . '/src_excluded';
    $destination = self::$sut . '/dst_excluded';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');
    File::dump(File::mkdir($src . '/.circleci') . '/config.yml', 'version: 2.1');

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // A previous install wrote both, unmodified since.
    File::dump(File::mkdir($destination) . '/phpstan.neon', 'parameters: []');
    File::dump(File::mkdir($destination . '/.circleci') . '/config.yml', 'version: 2.1');
    $this->stubPreviousTemplate($fm, $destination, [
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
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // The project edited the file after the previous install wrote it.
    File::dump(File::mkdir($destination) . '/phpstan.neon', "parameters:\n  level: 8");
    $this->stubPreviousTemplate($fm, $destination, ['phpstan.neon' => 'parameters: []']);

    $fm->snapshotTemplate();
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/phpstan.neon', 'A file the project edited is never removed.');
    $this->assertStringEqualsFile($destination . '/phpstan.neon', "parameters:\n  level: 8", 'The project edit is left untouched.');
  }

  public function testCopyFilesKeepsExcludedPathsWithoutRecordedHash(): void {
    $src = self::$sut . '/src_unverifiable';
    $destination = self::$sut . '/dst_unverifiable';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    // No previous version, so ownership cannot be established.
    File::dump(File::mkdir($destination) . '/phpstan.neon', 'parameters: []');
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/phpstan.neon', 'Without a recorded hash the file is left alone.');
  }

  public function testCopyFilesWritesNoManifest(): void {
    $src = self::$sut . '/src_no_manifest';
    $destination = self::$sut . '/dst_no_manifest';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump(File::mkdir($src . '/scripts') . '/provision.sh', 'echo 1');

    $config = $this->createConfig($destination, $src);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    $fm->copyFiles();

    $this->assertFileDoesNotExist($destination . '/.vortex-manifest.json', 'Install records nothing in the project.');
  }

  public function testCopyFilesRemovesExcludedPathsMatchedOnlyAfterRendering(): void {
    $src = self::$sut . '/src_rendered';
    $destination = self::$sut . '/dst_rendered';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/rector.php', 'paths: your_site');

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    // The project holds the rendered content, which the download never has.
    File::dump(File::mkdir($destination) . '/rector.php', 'paths: star_wars');
    $this->stubPreviousTemplate($fm, $destination, ['rector.php' => 'paths: your_site'], function (string $dir): void {
      File::dump($dir . '/rector.php', 'paths: star_wars');
    });

    $fm->snapshotTemplate();
    File::remove($src . '/rector.php');

    $fm->copyFiles();

    $this->assertFileDoesNotExist($destination . '/rector.php', 'Rendering resolves tokens that the download itself does not match.');
  }

  public function testCopyFilesRemovesExcludedPathsDeselectedByThisRun(): void {
    $src = self::$sut . '/src_deselected';
    $destination = self::$sut . '/dst_deselected';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/jest.config.js', 'module.exports = {};');

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    File::dump(File::mkdir($destination) . '/jest.config.js', 'module.exports = {};');

    // Discovery answers describe the project, which still has the tool, so
    // the render keeps the file even though this run deselects it.
    $this->stubPreviousTemplate($fm, $destination, ['jest.config.js' => 'module.exports = {};']);

    $fm->snapshotTemplate();
    File::remove($src . '/jest.config.js');

    $fm->copyFiles();

    $this->assertFileDoesNotExist($destination . '/jest.config.js', 'A tool the project has is still removable when this run deselects it.');
  }

  public function testCopyFilesRecordsReplacedProjectChanges(): void {
    $src = self::$sut . '/src_registry';
    $destination = self::$sut . '/dst_registry';
    File::dump(File::mkdir($src) . '/phpstan.neon', "parameters:\n  level: 9\n");

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $config->set(Config::VERSION, '1.41.0', TRUE);
    $fm = new FileManager($config);

    // The project edited the file the previous version installed.
    File::dump(File::mkdir($destination) . '/phpstan.neon', "parameters:\n  level: 8\n");
    $this->stubPreviousTemplate($fm, $destination, ['phpstan.neon' => "parameters:\n  level: 5\n"]);

    $fm->snapshotTemplate();
    $fm->copyFiles();

    $registry = $destination . '/' . UpdateRegistry::FILE;

    $this->assertEquals($registry, $fm->getRegistryFile());
    $this->assertStringEqualsFile($destination . '/phpstan.neon', "parameters:\n  level: 9\n", 'The update still replaces the project file.');
    $this->assertFileContainsString($registry, '## 1.40.0 to 1.41.0');
    $this->assertFileContainsString($registry, '### phpstan.neon');
    $this->assertFileContainsString($registry, '-  level: 5');
    $this->assertFileContainsString($registry, '+  level: 8');
    $this->assertFileContainsString($registry, '+  level: 9');
  }

  public function testCopyFilesRecordsNothingWithoutProjectChanges(): void {
    $src = self::$sut . '/src_no_registry';
    $destination = self::$sut . '/dst_no_registry';
    File::dump(File::mkdir($src) . '/phpstan.neon', "parameters:\n  level: 9\n");

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    File::dump(File::mkdir($destination) . '/phpstan.neon', "parameters:\n  level: 5\n");
    $this->stubPreviousTemplate($fm, $destination, ['phpstan.neon' => "parameters:\n  level: 5\n"]);

    $fm->snapshotTemplate();
    $fm->copyFiles();

    $this->assertNull($fm->getRegistryFile());
    $this->assertFileDoesNotExist($destination . '/' . UpdateRegistry::FILE, 'An untouched file leaves nothing to reconcile.');
  }

  public function testCopyFilesRemovesCommittedManifest(): void {
    $src = self::$sut . '/src_stale_manifest';
    $destination = self::$sut . '/dst_stale_manifest';
    File::dump(File::mkdir($src) . '/composer.json', '{}');

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);

    File::dump(File::mkdir($destination) . '/.vortex-manifest.json', '{"composer.json":"abc"}');

    $fm->copyFiles();

    $this->assertFileDoesNotExist($destination . '/.vortex-manifest.json', 'A manifest an earlier install left behind is removed.');
  }

  public function testCopyFilesKeepsManifestInDestinationThatIsNotVortexProject(): void {
    $src = self::$sut . '/src_foreign_manifest';
    $destination = self::$sut . '/dst_foreign_manifest';
    File::dump(File::mkdir($src) . '/composer.json', '{}');

    $config = $this->createConfig($destination, $src);
    $fm = new FileManager($config);

    File::dump(File::mkdir($destination) . '/.vortex-manifest.json', '{"owned":"by the project"}');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/.vortex-manifest.json', 'A destination that never ran Vortex keeps its own file.');
  }

  public function testCopyFilesKeepsPathsTheTemplateNeverShipped(): void {
    $src = self::$sut . '/src_unknown';
    $destination = self::$sut . '/dst_unknown';
    File::dump(File::mkdir($src) . '/composer.json', '{}');

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    File::dump(File::mkdir($destination) . '/phpstan.neon', 'project owned');
    File::dump(File::mkdir($destination . '/web/modules/custom/mymodule') . '/mymodule.info.yml', 'name: My module');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/phpstan.neon', 'A path the template never shipped is left alone.');
    $this->assertFileExists($destination . '/web/modules/custom/mymodule/mymodule.info.yml', 'Project-authored content is left alone.');
  }

  public function testCopyFilesKeepsExcludedPathsForNonVortexProject(): void {
    $src = self::$sut . '/src_fresh';
    $destination = self::$sut . '/dst_fresh';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump($src . '/phpstan.neon', 'parameters: []');

    $config = $this->createConfig($destination, $src);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    File::dump(File::mkdir($destination) . '/phpstan.neon', 'project owned');
    File::remove($src . '/phpstan.neon');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/phpstan.neon', 'A destination that is not a Vortex project is never pruned.');
  }

  public function testCopyFilesKeepsHarnessPaths(): void {
    $src = self::$sut . '/src_harness';
    $destination = self::$sut . '/dst_harness';
    File::dump(File::mkdir($src) . '/composer.json', '{}');
    File::dump(File::mkdir($src . '/.vortex') . '/CLAUDE.md', 'harness');

    $config = $this->createConfig($destination, $src);
    $config->set(Config::IS_VORTEX_PROJECT, TRUE, TRUE);
    $fm = new FileManager($config);
    $fm->snapshotTemplate();

    File::dump(File::mkdir($destination . '/.vortex') . '/CLAUDE.md', 'project owned');
    File::remove($src . '/.vortex');

    $fm->copyFiles();

    $this->assertFileExists($destination . '/.vortex/CLAUDE.md', "The harness never ships, so a matching path is the project's own.");
  }

  public function testCopyFilesRemovesObsoleteScriptsVortex(): void {
    // The destination mimics an upgrade from a Vortex version that shipped
    // scripts at 'scripts/vortex/'; the 'drevops/vortex-tooling' Composer
    // package ships them instead, so the copy removes the legacy directory.
    $src = self::$sut . '/src_obsolete';
    $destination = self::$sut . '/dst_obsolete';
    File::mkdir($src);
    File::mkdir($destination . '/scripts/vortex');
    File::dump($src . '/test.txt', 'new');
    File::dump($destination . '/scripts/vortex/legacy.sh', 'legacy');
    File::dump($destination . '/scripts/keep.sh', 'custom');

    $config = $this->createConfig($destination, $src);
    $fm = new FileManager($config);

    $fm->copyFiles();

    $this->assertDirectoryDoesNotExist($destination . '/scripts/vortex', 'Legacy scripts/vortex/ directory removed after copy.');
    $this->assertFileExists($destination . '/scripts/keep.sh', 'Sibling custom scripts/ entries preserved.');
    $this->assertFileExists($destination . '/test.txt', 'New files copied from source.');
  }

  public function testRemoveObsoletePathsSilentOnMissing(): void {
    $destination = self::$sut . '/dst_no_obsolete';
    File::mkdir($destination);

    $config = $this->createConfig($destination);
    $fm = new FileManager($config);

    $fm->removeObsoletePaths();

    $this->addToAssertionCount(1);
  }

  /**
   * @param array<string, bool|null> $config_values
   *   Config keys to set before preparing, keyed by constant.
   * @param string|null $dotenv
   *   Content for the project's '.env', or NULL to write none.
   * @param bool $with_database_file
   *   Seed the data directory with an already-fetched database dump.
   * @param string $expected_message
   *   Substring the returned messages must contain.
   */
  #[DataProvider('dataProviderPrepareDemo')]
  public function testPrepareDemo(array $config_values, ?string $dotenv, bool $with_database_file, string $expected_message): void {
    $destination = self::$sut;

    if ($dotenv !== NULL) {
      File::dump($destination . '/.env', $dotenv);
    }

    if ($with_database_file) {
      File::dump(File::mkdir($destination . '/.data') . '/db.sql', 'existing');
    }

    $config = $this->createConfig($destination);
    foreach ($config_values as $name => $value) {
      $config->set($name, $value);
    }

    $result = (new FileManager($config))->prepareDemo($this->createMock(Downloader::class));

    $messages = is_array($result) ? $result : [$result];
    $this->assertStringContainsString($expected_message, implode(PHP_EOL, array_map(strval(...), $messages)));
  }

  public static function dataProviderPrepareDemo(): \Iterator {
    $dotenv = "VORTEX_FETCH_DB_URL=https://example.com/db.sql\nVORTEX_DB_DIR=./.data\nVORTEX_DB_FILE=db.sql\n";

    yield 'not a demo' => [[], NULL, FALSE, 'Not a demo mode.'];
    yield 'fetch skipped' => [[Config::IS_DEMO => TRUE, Config::IS_DEMO_DB_FETCH_SKIP => TRUE], NULL, FALSE, 'Skipping demo database fetch'];
    yield 'no fetch url' => [[Config::IS_DEMO => TRUE], '', FALSE, 'No database fetch URL provided'];
    yield 'database already fetched' => [[Config::IS_DEMO => TRUE], $dotenv, TRUE, 'already exists'];
    yield 'data directory created' => [[Config::IS_DEMO => TRUE], $dotenv, FALSE, 'Created data directory'];
    yield 'database fetched' => [[Config::IS_DEMO => TRUE], $dotenv, FALSE, 'Fetched demo database'];
  }

  public function testPrepareDemoDownloadsFromTheConfiguredUrl(): void {
    $destination = self::$sut;
    File::dump($destination . '/.env', "VORTEX_FETCH_DB_URL=https://example.com/db.sql\nVORTEX_DB_DIR=./.data\nVORTEX_DB_FILE=db.sql\n");

    $config = $this->createConfig($destination);
    $config->set(Config::IS_DEMO, TRUE);

    $downloader = $this->createMock(Downloader::class);
    $downloader->expects($this->once())
      ->method('download')
      ->with('https://example.com/db.sql', $this->stringContains('db.sql'));

    (new FileManager($config))->prepareDemo($downloader);
  }

  /**
   * Snapshot a stubbed download of the version the project runs.
   *
   * @param \DrevOps\VortexInstaller\Utils\FileManager $fm
   *   The file manager to snapshot into.
   * @param string $destination
   *   The project directory.
   * @param array<string, string> $files
   *   Content the previous version installed, keyed by relative path.
   * @param callable|null $render
   *   Callback turning the download into installable content.
   */
  protected function stubPreviousTemplate(FileManager $fm, string $destination, array $files, ?callable $render = NULL): void {
    File::dump($destination . '/README.md', '[![Vortex](https://img.shields.io/badge/Vortex-1.40.0-65ACBC.svg)](https://github.com/drevops/vortex)');

    $downloader = $this->createStub(RepositoryDownloader::class);
    $downloader->method('download')->willReturnCallback(function (Artifact $artifact, ?string $dir = NULL) use ($files): string {
      foreach ($files as $path => $contents) {
        File::dump($dir . '/' . $path, $contents);
      }

      return $artifact->getRef();
    });

    $fm->snapshotPreviousTemplate($downloader, Artifact::create('https://github.com/drevops/vortex.git', '1.40.0'), $render);
  }

}
