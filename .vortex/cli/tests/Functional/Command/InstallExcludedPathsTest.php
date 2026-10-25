<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Command;

use DrevOps\VortexCli\Command\AbstractInstallCommand;
use DrevOps\VortexCli\Command\InstallCommand;
use DrevOps\VortexCli\Command\UpdateCommand;
use DrevOps\VortexCli\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\FileManager;
use DrevOps\VortexCli\Utils\Git;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for removing excluded paths from the destination.
 */
#[CoversClass(FileManager::class)]
#[CoversClass(AbstractInstallCommand::class)]
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

    $this->runVerb(new InstallCommand(), self::PROMPTS_ALL_TOOLS, $ref);
    $this->assertFileExists(static::$sut . '/jest.config.js', 'A selected tool ships its configuration.');
    $this->assertFileExists(static::$sut . '/phpstan.neon', 'A selected tool ships its configuration.');

    $this->runVerb(new UpdateCommand(), self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    $this->assertFileDoesNotExist(static::$sut . '/jest.config.js', 'A deselected tool loses its unmodified configuration.');
    $this->assertFileDoesNotExist(static::$sut . '/phpstan.neon', 'A deselected tool loses its unmodified configuration.');
    $this->assertFileExists(static::$sut . '/phpcs.xml', 'A tool that stayed selected keeps its configuration.');
    $this->assertFileExists(static::$sut . '/behat.php', 'A tool that stayed selected keeps its configuration.');
  }

  public function testUpdateKeepsModifiedExcludedPaths(): void {
    $ref = $this->templateRef();

    $this->runVerb(new InstallCommand(), self::PROMPTS_ALL_TOOLS, $ref);

    $modified = "parameters:\n  level: 8\n";
    File::dump(static::$sut . '/phpstan.neon', $modified);

    $this->runVerb(new UpdateCommand(), self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    $this->assertFileExists(static::$sut . '/phpstan.neon', 'A file the project edited is never removed.');
    $this->assertStringEqualsFile(static::$sut . '/phpstan.neon', $modified, 'The project edit is left untouched.');
    $this->assertFileDoesNotExist(static::$sut . '/jest.config.js', 'Unmodified siblings are still removed.');
  }

  public function testUpdateKeepsProjectAuthoredPaths(): void {
    $ref = $this->templateRef();

    $this->runVerb(new InstallCommand(), self::PROMPTS_ALL_TOOLS, $ref);

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

    $this->runVerb(new UpdateCommand(), self::PROMPTS_WITHOUT_TEST_TOOLS, $ref);

    foreach ($project_files as $path => $contents) {
      $this->assertFileExists(static::$sut . '/' . $path, sprintf('Project-authored "%s" kept in the destination.', $path));
      $this->assertStringEqualsFile(static::$sut . '/' . $path, $contents, sprintf('Project-authored "%s" kept its contents.', $path));
    }
  }

  public function testNothingRemovedFromDestinationThatIsNotVortexProject(): void {
    $shipped = [
      'phpstan.neon' => 'parameters: []',
      'jest.config.js' => 'module.exports = {};',
    ];
    foreach ($shipped as $path => $contents) {
      File::dump(static::$sut . '/' . $path, $contents);
    }

    $this->runVerb(new InstallCommand(), self::PROMPTS_WITHOUT_TEST_TOOLS, $this->templateRef());

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
   * Run a verb non-interactively against the system under test.
   */
  protected function runVerb(AbstractInstallCommand $command, string $prompts, string $ref): void {
    $executable_finder = $this->createMock(ExecutableFinder::class);
    $executable_finder->method('find')->willReturnCallback(fn(string $name): string => '/usr/bin/' . $name);
    $command->setExecutableFinder($executable_finder);

    static::applicationInitFromCommand($command);

    Env::put(Config::IS_DEMO_DB_FETCH_SKIP, '1');

    $this->applicationRun([
      '--' . AbstractInstallCommand::OPTION_NO_INTERACTION => TRUE,
      '--' . AbstractInstallCommand::OPTION_URI => sprintf('%s#%s', File::dir(static::$root), $ref),
      '--' . AbstractInstallCommand::OPTION_DESTINATION => static::$sut,
      '--' . InstallCommand::OPTION_PROMPTS => $prompts,
    ]);
  }

}
