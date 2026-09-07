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
use DrevOps\VortexInstaller\Utils\UpdateRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for removing excluded paths from the destination.
 */
#[CoversClass(FileManager::class)]
#[CoversClass(InstallCommand::class)]
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
    $this->assertFileExists(static::$sut . '/behat.yml', 'A tool that stayed selected keeps its configuration.');
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
