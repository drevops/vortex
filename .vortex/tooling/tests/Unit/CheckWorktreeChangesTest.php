<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\File\File;
use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the working tree check script.
 *
 * The script runs against a real Git repository created in a sandbox
 * directory, so the reported changes come from Git itself.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class CheckWorktreeChangesTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envUnset('VORTEX_CHECK_WORKTREE_CHANGES_IGNORE');

    // Stop Git from discovering a repository above the sandbox, so a sandbox
    // without a repository is reported as such.
    $this->envSet('GIT_CEILING_DIRECTORIES', self::$tmp);
  }

  #[DataProvider('dataProviderCheckWorktreeChanges')]
  public function testCheckWorktreeChanges(array $env_vars, array $files, array $expected, bool $expect_error = FALSE, bool $init_repo = TRUE): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    $root = self::$tmp . '/project_' . uniqid();
    File::mkdir($root);

    if ($init_repo) {
      $this->createRepository($root);
    }

    // Each entry is appended to the file, creating the file when missing.
    foreach ($files as $path => $content) {
      $file = $root . '/' . $path;
      File::dump($file, (File::exists($file) ? File::read($file) : '') . $content);
    }

    if ($expect_error) {
      try {
        $this->runScript('src/vortex-check-worktree-changes', 1, $root);
      }
      catch (QuitErrorException $e) {
        $this->assertStringContainsOrNot($e->getOutput(), $expected);
        throw $e;
      }

      return;
    }

    $output = $this->runScript('src/vortex-check-worktree-changes', NULL, $root);

    $this->assertStringContainsOrNot($output, $expected);
  }

  public static function dataProviderCheckWorktreeChanges(): array {
    return [
      'clean working tree passes' => [
        [],
        [],
        [
          '* Started working tree check.',
          '* Finished working tree check.',
          '! The build left changes in the working tree.',
        ],
      ],

      'modified tracked file fails' => [
        [],
        ['composer.json' => 'Modified by the build.' . PHP_EOL],
        [
          '* The build modified or created these files:',
          '*  M composer.json',
          '* Run "ahoy build" locally and commit the resulting changes.',
          '* Add paths that are generated during the build and are not committed to VORTEX_CHECK_WORKTREE_CHANGES_IGNORE.',
          '* The build left changes in the working tree.',
          '! Finished working tree check.',
        ],
        TRUE,
      ],

      'untracked file fails' => [
        [],
        ['build-manifest.json' => '{}'],
        [
          '* ?? build-manifest.json',
          '* The build left changes in the working tree.',
        ],
        TRUE,
      ],

      'file ignored by Git passes' => [
        [],
        ['.logs/build.log' => 'log'],
        [
          '* Finished working tree check.',
          '! .logs',
        ],
      ],

      'shipped ignore list covers the paths written by the pipeline' => [
        [],
        [
          'docker-compose.yml' => '# Processed to run in CI.' . PHP_EOL,
          'web/sites/default/docker-compose.yml' => 'services: {}',
          'db_cache_branch' => 'develop',
          'db_cache_fallback_yes' => 'yes',
          'db_cache_timestamp' => '20260101',
          'codecov' => 'binary',
          'codecov.SHA256SUM' => 'sum',
          'codecov.SHA256SUM.sig' => 'sig',
        ],
        [
          '* Ignoring "docker-compose.yml".',
          '* Ignoring "*/docker-compose.yml".',
          '* Ignoring "db_cache_*".',
          '* Ignoring "codecov".',
          '* Ignoring "codecov.SHA256SUM".',
          '* Ignoring "codecov.SHA256SUM.sig".',
          '* Finished working tree check.',
        ],
      ],

      'shipped ignore list does not cover the Codecov config file' => [
        [],
        ['codecov.yml' => 'coverage: {}'],
        [
          '* ?? codecov.yml',
          '* The build left changes in the working tree.',
        ],
        TRUE,
      ],

      'custom ignore list excludes a path' => [
        ['VORTEX_CHECK_WORKTREE_CHANGES_IGNORE' => 'build-manifest.json'],
        ['build-manifest.json' => '{}'],
        [
          '* Ignoring "build-manifest.json".',
          '* Finished working tree check.',
        ],
      ],

      'custom ignore list replaces the shipped one' => [
        ['VORTEX_CHECK_WORKTREE_CHANGES_IGNORE' => 'build-manifest.json'],
        ['docker-compose.yml' => '# Processed to run in CI.' . PHP_EOL],
        [
          '*  M docker-compose.yml',
          '! Ignoring "docker-compose.yml".',
          '* The build left changes in the working tree.',
        ],
        TRUE,
      ],

      'blank entries in the ignore list are skipped' => [
        ['VORTEX_CHECK_WORKTREE_CHANGES_IGNORE' => ' , build-manifest.json , '],
        ['build-manifest.json' => '{}'],
        [
          '* Ignoring "build-manifest.json".',
          '! Ignoring "".',
          '* Finished working tree check.',
        ],
      ],

      'ignore list of separators only checks every path' => [
        ['VORTEX_CHECK_WORKTREE_CHANGES_IGNORE' => ','],
        ['docker-compose.yml' => '# Processed to run in CI.' . PHP_EOL],
        [
          '! Ignoring ',
          '*  M docker-compose.yml',
          '* The build left changes in the working tree.',
        ],
        TRUE,
      ],

      'fails outside a Git repository' => [
        [],
        [],
        [
          '* The current directory is not a Git repository.',
          '! Finished working tree check.',
        ],
        TRUE,
        FALSE,
      ],
    ];
  }

  public function testMissingGit(): void {
    $this->mockCommandExists();
    $this->mockCommandMissing('git');

    $this->runScriptError('src/vortex-check-worktree-changes', "Command 'git' is not available.");
  }

  public function testStatusFailure(): void {
    $root = self::$tmp . '/project_' . uniqid();
    File::mkdir($root);
    $this->createRepository($root);

    // Fail only the status read; every other command runs for real.
    $this->registerMock('exec', 'DrevOps\\VortexTooling', function (string $command, mixed &$output = NULL, mixed &$result_code = NULL): string|false {
      if (str_starts_with($command, "git 'status'")) {
        $output = [];
        $result_code = 128;

        return '';
      }

      return \exec($command, $output, $result_code);
    });

    try {
      $this->runScript('src/vortex-check-worktree-changes', 1, $root);
    }
    catch (QuitErrorException $e) {
      $this->assertStringContainsOrNot($e->getOutput(), [
        '* Unable to read the working tree status.',
        '! Finished working tree check.',
      ]);
      throw $e;
    }
  }

  /**
   * Create a Git repository with committed files.
   */
  protected function createRepository(string $root): void {
    File::dump($root . '/.gitignore', '.logs' . PHP_EOL);
    File::dump($root . '/composer.json', '{}' . PHP_EOL);
    File::dump($root . '/docker-compose.yml', 'services: {}' . PHP_EOL);

    $commands = [
      'git init --quiet',
      'git add --all',
      'git -c user.name=Test -c user.email=test@example.com -c commit.gpgsign=false commit --quiet --message=Initial',
    ];

    foreach ($commands as $command) {
      exec(sprintf('cd %s && %s 2>&1', escapeshellarg($root), $command), $output, $exit_code);
      if ($exit_code !== 0) {
        throw new \RuntimeException(sprintf('Unable to run "%s": %s', $command, implode(PHP_EOL, $output)));
      }
    }
  }

}
