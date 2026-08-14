<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Command;

use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\FileManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Functional tests for removing excluded paths from the destination.
 */
#[CoversClass(FileManager::class)]
#[CoversClass(InstallCommand::class)]
class InstallExcludedPathsTest extends FunctionalTestCase {

  /**
   * Selection that drops Jest, PHPStan and PHPUnit but keeps PHPCS and Behat.
   */
  const PROMPTS_WITHOUT_TEST_TOOLS = '{"tools":["behat","dclint","eslint","hadolint","phpcs","rector","stylelint","twig_cs_fixer"]}';

  #[DataProvider('dataProviderExcludedPaths')]
  public function testExcludedPaths(bool $is_vortex_project, array $existing, string $prompts, array $absent, array $present): void {
    foreach ($existing as $path => $contents) {
      File::dump(static::$sut . '/' . $path, $contents);
    }

    if ($is_vortex_project) {
      File::dump(static::$sut . '/README.md', '[![Vortex](https://img.shields.io/badge/Vortex-1.40.0-65ACBC.svg)](https://github.com/drevops/vortex)');
    }

    $this->runInstall($prompts);

    foreach ($absent as $path) {
      $this->assertFileDoesNotExist(static::$sut . '/' . $path, sprintf('Path "%s" removed from the destination.', $path));
    }

    foreach ($present as $path => $contents) {
      $this->assertFileExists(static::$sut . '/' . $path, sprintf('Path "%s" kept in the destination.', $path));

      if ($contents !== NULL) {
        $this->assertStringEqualsFile(static::$sut . '/' . $path, $contents, sprintf('Path "%s" kept its contents.', $path));
      }
    }
  }

  public static function dataProviderExcludedPaths(): \Iterator {
    yield 'excluded tool paths removed from an existing project' => [
      TRUE,
      [
        'jest.config.js' => 'module.exports = {};',
        'phpstan.neon' => 'parameters: []',
        'phpunit.xml' => '<phpunit/>',
        'tests/phpunit/bootstrap.php' => '<?php',
        'tests/phpunit/Drupal/DatabaseSettingsTest.php' => '<?php',
      ],
      self::PROMPTS_WITHOUT_TEST_TOOLS,
      [
        'jest.config.js',
        'phpstan.neon',
        'phpunit.xml',
        'tests/phpunit/bootstrap.php',
        'tests/phpunit/Drupal/DatabaseSettingsTest.php',
        // Emptied by the removal of every path the template shipped into it.
        'tests/phpunit/Drupal',
      ],
      [
        // A tool that stayed selected keeps its shipped configuration.
        'phpcs.xml' => NULL,
        'behat.yml' => NULL,
      ],
    ];
    yield 'project-authored paths inside an excluded directory are kept' => [
      TRUE,
      [
        'phpunit.xml' => '<phpunit/>',
        'tests/phpunit/bootstrap.php' => '<?php',
        'tests/phpunit/MyProjectTest.php' => '<?php // Project owned.',
      ],
      self::PROMPTS_WITHOUT_TEST_TOOLS,
      [
        'phpunit.xml',
        'tests/phpunit/bootstrap.php',
      ],
      [
        // Removal is path-level, so a file the template never shipped stays
        // even when its directory is otherwise emptied.
        'tests/phpunit/MyProjectTest.php' => '<?php // Project owned.',
      ],
    ];
    yield 'project-authored paths kept in an existing project' => [
      TRUE,
      [
        'jest.config.js' => 'module.exports = {};',
        'custom-notes.md' => 'Project notes.',
        'web/modules/custom/mymodule/mymodule.info.yml' => 'name: My module',
        'web/modules/custom/mymodule/js/mymodule.test.js' => "test('kept', () => {});",
      ],
      self::PROMPTS_WITHOUT_TEST_TOOLS,
      [
        'jest.config.js',
      ],
      [
        // Never shipped by the template, so never a candidate for removal.
        'custom-notes.md' => 'Project notes.',
        'web/modules/custom/mymodule/mymodule.info.yml' => 'name: My module',
        // Matched only by a glob over project content, not by a shipped path.
        'web/modules/custom/mymodule/js/mymodule.test.js' => "test('kept', () => {});",
      ],
    ];
    yield 'harness paths kept in an existing project' => [
      TRUE,
      [
        '.vortex/CLAUDE.md' => 'Project owned.',
      ],
      self::PROMPTS_WITHOUT_TEST_TOOLS,
      [],
      [
        // The harness is stripped unconditionally rather than by selection.
        '.vortex/CLAUDE.md' => 'Project owned.',
      ],
    ];
    yield 'nothing removed from a destination that is not a Vortex project' => [
      FALSE,
      [
        'jest.config.js' => 'module.exports = {};',
        'phpstan.neon' => 'parameters: []',
      ],
      self::PROMPTS_WITHOUT_TEST_TOOLS,
      [],
      [
        'jest.config.js' => 'module.exports = {};',
        'phpstan.neon' => 'parameters: []',
      ],
    ];
  }

  /**
   * Run a non-interactive install into the system under test.
   */
  protected function runInstall(string $prompts): void {
    $executable_finder = $this->createMock(ExecutableFinder::class);
    $executable_finder->method('find')->willReturnCallback(fn(string $command): string => '/usr/bin/' . $command);

    $install_command = new InstallCommand();
    $install_command->setExecutableFinder($executable_finder);

    static::applicationInitFromCommand($install_command);

    Env::put(Config::IS_DEMO_DB_FETCH_SKIP, '1');

    $this->applicationRun([
      '--' . InstallCommand::OPTION_NO_INTERACTION => TRUE,
      '--' . InstallCommand::OPTION_URI => File::dir(static::$root),
      '--' . InstallCommand::OPTION_DESTINATION => static::$sut,
      '--' . InstallCommand::OPTION_PROMPTS => $prompts,
    ]);
  }

}
