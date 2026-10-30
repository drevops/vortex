<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Command;

use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\FileManager;
use DrevOps\VortexInstaller\Utils\Git;
use DrevOps\VortexInstaller\Utils\InstallReport;
use DrevOps\VortexInstaller\Utils\UpdateRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for removing excluded paths from the destination.
 */
#[CoversClass(FileManager::class)]
#[CoversClass(InstallCommand::class)]
#[CoversClass(InstallReport::class)]
#[CoversClass(UpdateRegistry::class)]
class InstallExcludedPathsTest extends FunctionalTestCase {

  /**
   * Selection that keeps every tool.
   */
  const PROMPTS_ALL_TOOLS = '{"tools":["behat","dclint","eslint","hadolint","jest","phpcs","phpstan","phpunit","rector","stylelint","twig_cs_fixer"]}';

  /**
   * Selection that drops Jest, PHPStan and PHPUnit but keeps PHPCS and Behat.
   */
  const PROMPTS_WITHOUT_TEST_TOOLS = '{"tools":["behat","dclint","eslint","hadolint","phpcs","rector","stylelint","twig_cs_fixer"]}';

  public function testUpdateRemovesUnmodifiedExcludedPaths(): void {
    $ref = $this->templateRef();

    $this->runInstall(self::PROMPTS_ALL_TOOLS, $ref);
    $this->assertFileExists(static::$sut . '/jest.config.js', 'A selected tool ships its configuration.');
    $this->assertFileExists(static::$sut . '/phpstan.neon', 'A selected tool ships its configuration.');

    $this->runInstall(self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    $this->assertFileDoesNotExist(static::$sut . '/jest.config.js', 'A deselected tool loses its unmodified configuration.');
    $this->assertFileDoesNotExist(static::$sut . '/phpstan.neon', 'A deselected tool loses its unmodified configuration.');
    $this->assertFileExists(static::$sut . '/phpcs.xml', 'A tool that stayed selected keeps its configuration.');
    $this->assertFileExists(static::$sut . '/behat.php', 'A tool that stayed selected keeps its configuration.');
  }

  public function testUpdateKeepsModifiedExcludedPaths(): void {
    $ref = $this->templateRef();

    $this->runInstall(self::PROMPTS_ALL_TOOLS, $ref);

    $modified = "parameters:\n  level: 8\n";
    File::dump(static::$sut . '/phpstan.neon', $modified);

    $this->runInstall(self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    $this->assertFileExists(static::$sut . '/phpstan.neon', 'A file the project edited is never removed.');
    $this->assertStringEqualsFile(static::$sut . '/phpstan.neon', $modified, 'The project edit is left untouched.');
    $this->assertFileDoesNotExist(static::$sut . '/jest.config.js', 'Unmodified siblings are still removed.');
  }

  public function testUpdateKeepsProjectAuthoredPaths(): void {
    $ref = $this->templateRef();

    $this->runInstall(self::PROMPTS_ALL_TOOLS, $ref);

    $project_files = [
      'custom-notes.md' => "Project notes.\n",
      'scripts/custom-deploy.sh' => "echo deploy\n",
      'web/modules/custom/mymodule/mymodule.info.yml' => "name: My module\n",
      // Matched only by a glob over project content, not by a shipped path.
      'web/modules/custom/mymodule/js/mymodule.test.js' => "test('kept', () => {});\n",
    ];
    foreach ($project_files as $path => $contents) {
      File::dump(static::$sut . '/' . $path, $contents);
    }

    $this->runInstall(self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    foreach ($project_files as $path => $contents) {
      $this->assertFileExists(static::$sut . '/' . $path, sprintf('Project-authored "%s" kept in the destination.', $path));
      $this->assertStringEqualsFile(static::$sut . '/' . $path, $contents, sprintf('Project-authored "%s" kept its contents.', $path));
    }
  }

  public function testUpdateRecordsReplacedProjectChanges(): void {
    $ref = $this->templateRef();

    $this->runInstall(self::PROMPTS_ALL_TOOLS, $ref);

    // A file the template keeps shipping, so the update replaces it.
    $shipped = File::read(static::$sut . '/.ahoy.yml');
    File::dump(static::$sut . '/.ahoy.yml', $shipped . PHP_EOL . '# Project addition.' . PHP_EOL);

    $this->runInstall(self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    $registry = static::$sut . '/' . UpdateRegistry::FILE;

    $this->assertFileExists($registry, 'A replaced project change is recorded.');
    $this->assertFileContainsString($registry, '### .ahoy.yml');
    // Rendering the installed version reproduces token replacements and the
    // theme directory rename, so files the project left alone match it.
    $this->assertFileNotContainsString($registry, '### composer.json', 'A token-processed file the project did not change is not recorded.');
    $this->assertFileNotContainsString($registry, '### web/themes/custom/star_wars/package.json', 'A file under a renamed directory is not recorded.');
    $this->assertFileContainsString($registry, '+# Project addition.');
    $this->assertFileNotContainsString(static::$sut . '/.ahoy.yml', '# Project addition.', 'The update still replaces the project file.');
  }

  public function testInstallAndUpdateWriteReport(): void {
    $ref = $this->templateRef();

    $this->runInstall(self::PROMPTS_ALL_TOOLS, $ref);

    $first = $this->findReport();
    $this->assertFileContainsString($first, '| `added` | [composer.json](../composer.json) |  |', 'A fresh installation adds every file.');
    $this->assertFileNotContainsString($first, '**From:**', 'A fresh installation has no previous version.');
    $this->assertNoFailedFiles($first);
    $this->assertDirectoryDoesNotExist(substr($first, 0, -strlen(InstallReport::EXTENSION)), 'A fresh installation changes no existing file, so it writes no diff.');
    $this->assertApplicationOutputContains(['Status and diff of every file are recorded in:', '.logs/' . InstallReport::PREFIX]);

    File::dump(static::$sut . '/.ahoy.yml', File::read(static::$sut . '/.ahoy.yml') . PHP_EOL . '# Project addition.' . PHP_EOL);
    File::dump(static::$sut . '/phpstan.neon', "parameters:\n  level: 8\n");

    $this->runInstall(self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    $this->assertFileExists($first, 'An update leaves the earlier report in place.');
    $report = $this->findReport([$first]);
    $this->assertFileContainsString($report, '- **From:** ', 'An update names the version the project ran.');
    $this->assertNoFailedFiles($report);

    $name = basename($report, InstallReport::EXTENSION);
    $expected = [
      sprintf('| `overwritten` | [.ahoy.yml](../.ahoy.yml) | [diff](%s/.ahoy.yml.diff) |', $name),
      '| `unchanged` | [.editorconfig](../.editorconfig) |  |',
      sprintf('| `removed` | `jest.config.js` | [diff](%s/jest.config.js.diff) |', $name),
      '| `kept` | [phpstan.neon](../phpstan.neon) |  |',
    ];
    foreach ($expected as $row) {
      $this->assertFileContainsString($report, $row);
    }

    $diff_dir = dirname($report) . '/' . $name;
    $this->assertFileContainsString($diff_dir . '/.ahoy.yml.diff', '-# Project addition.', 'The diff shows the project change the update replaced.');
    $this->assertFileContainsString($diff_dir . '/jest.config.js.diff', '+++ /dev/null');

    $this->assertApplicationOutputContains(['Finished updating Vortex', 'Files: ', 'Status and diff of every file are recorded in:', 'Project changes replaced by this update are recorded in:']);
  }

  public function testUpdateRemovesCommittedManifest(): void {
    $ref = $this->templateRef();

    $this->runInstall(self::PROMPTS_ALL_TOOLS, $ref);
    $this->assertFileDoesNotExist(static::$sut . '/.vortex-manifest.json', 'Install records nothing in the project.');

    File::dump(static::$sut . '/.vortex-manifest.json', '{"composer.json":"abc"}');

    $this->runInstall(self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    $this->assertFileDoesNotExist(static::$sut . '/.vortex-manifest.json', 'A manifest an earlier install left behind is removed.');
  }

  public function testNothingRemovedFromDestinationThatIsNotVortexProject(): void {
    $shipped = [
      'phpstan.neon' => 'parameters: []',
      'jest.config.js' => 'module.exports = {};',
    ];
    foreach ($shipped as $path => $contents) {
      File::dump(static::$sut . '/' . $path, $contents);
    }

    $this->runInstall(self::PROMPTS_WITHOUT_TEST_TOOLS, $this->templateRef());

    foreach ($shipped as $path => $contents) {
      $this->assertStringEqualsFile(static::$sut . '/' . $path, $contents, sprintf('Path "%s" kept its contents.', $path));
    }
  }

  /**
   * Assert that a report lists no file the copy failed to write.
   *
   * The status legend above the summary names every status, so only the
   * summary and the file list are searched.
   */
  protected function assertNoFailedFiles(string $report): void {
    $content = File::read($report);
    $listing = substr($content, (int) strpos($content, '## Summary'));

    $this->assertStringNotContainsString('| `' . InstallReport::STATUS_FAILED . '` |', $listing, 'Every template file is written, symlinks included.');
  }

  /**
   * Get the path of the only install report not written by an earlier run.
   *
   * @param array<int, string> $earlier
   *   Reports written by earlier runs.
   */
  protected function findReport(array $earlier = []): string {
    $reports = glob(static::$sut . '/' . InstallReport::DIR . '/' . InstallReport::PREFIX . '*' . InstallReport::EXTENSION);

    $this->assertIsArray($reports);

    $reports = array_values(array_diff($reports, $earlier));
    $this->assertCount(1, $reports, 'The run writes 1 install report.');

    return $reports[0];
  }

  /**
   * Get the reference the template is installed from.
   */
  protected function templateRef(): string {
    return (new Git(File::dir(static::$root)))->getLastShortCommitId();
  }

  /**
   * Run a non-interactive install into the system under test.
   */
  protected function runInstall(string $prompts, string $ref): void {
    $executable_finder = $this->createMock(ExecutableFinder::class);
    $executable_finder->method('find')->willReturnCallback(fn(string $command): string => '/usr/bin/' . $command);

    $install_command = new InstallCommand();
    $install_command->setExecutableFinder($executable_finder);

    static::applicationInitFromCommand($install_command);

    Env::put(Config::IS_DEMO_DB_FETCH_SKIP, '1');

    $this->applicationRun([
      '--' . InstallCommand::OPTION_NO_INTERACTION => TRUE,
      '--' . InstallCommand::OPTION_URI => sprintf('%s#%s', File::dir(static::$root), $ref),
      '--' . InstallCommand::OPTION_DESTINATION => static::$sut,
      '--' . InstallCommand::OPTION_PROMPTS => $prompts,
    ]);
  }

}
