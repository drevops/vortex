<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for reset script.
 *
 * The script runs against a sandboxed project fixture directory: filesystem
 * operations are real, while git commands are mocked.
 */
#[Group('utility')]
class ResetTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('WEBROOT', 'web');

    // Tests share the process: reset argv so a '--hard' flag from a previous
    // case does not leak into cases that expect the default soft reset.
    $GLOBALS['argv'] = ['vortex-reset'];
  }

  #[DataProvider('dataProviderReset')]
  public function testReset(array $env_vars, ?\Closure $before, array $expected, ?array $argv = NULL, bool $expect_error = FALSE, ?\Closure $after = NULL): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    if ($argv !== NULL) {
      $GLOBALS['argv'] = $argv;
    }

    $root = self::$tmp . '/project_' . uniqid();
    mkdir($root, 0755, TRUE);

    if ($before instanceof \Closure) {
      $before($this, $root);
    }

    if ($expect_error) {
      try {
        $this->runScript('src/vortex-reset', 1, $root);
      }
      catch (QuitErrorException $e) {
        if (!empty($expected)) {
          $this->assertStringContainsOrNot($e->getOutput(), $expected);
        }

        if ($after instanceof \Closure) {
          $after($this, $root);
        }
        throw $e;
      }
      return;
    }

    $output = $this->runScript('src/vortex-reset', NULL, $root);

    $this->assertStringContainsOrNot($output, $expected);

    if ($after instanceof \Closure) {
      $after($this, $root);
    }
  }

  public static function dataProviderReset(): array {
    return [
      'soft default' => [
        [],
        static function (self $test, string $root): void {
          static::createFileTree($root, [
            'vendor/composer.json' => 'x',
            'web/core/lib/core.txt' => 'x',
            'web/modules/contrib/mod/mod.info.yml' => 'x',
            'web/modules/custom/keep/keep.info.yml' => 'keep',
            'web/themes/custom/t1/build/script.js' => 'x',
            'web/themes/custom/t1/scss/_components.scss' => 'x',
            'web/themes/custom/t1/scss/main.scss' => 'keep',
            'sub/node_modules/pkg/index.js' => 'x',
          ]);
        },
        [
          '* [INFO] Started reset.',
          '* [ OK ] Finished reset.',
          '! [TASK] Changing permissions',
          '! [TASK] Resetting repository files.',
          '! [TASK] Removing all untracked files.',
          '! [TASK] Removing empty directories.',
        ],
        NULL,
        FALSE,
        static function (self $test, string $root): void {
          $test->assertDirectoryDoesNotExist($root . '/vendor');
          $test->assertDirectoryDoesNotExist($root . '/web/core');
          $test->assertDirectoryDoesNotExist($root . '/web/modules/contrib');
          $test->assertFileExists($root . '/web/modules/custom/keep/keep.info.yml');
          $test->assertDirectoryDoesNotExist($root . '/web/themes/custom/t1/build');
          $test->assertFileDoesNotExist($root . '/web/themes/custom/t1/scss/_components.scss');
          $test->assertFileExists($root . '/web/themes/custom/t1/scss/main.scss');
          $test->assertDirectoryDoesNotExist($root . '/sub/node_modules');
          // Soft reset does not sweep empty directories.
          $test->assertDirectoryExists($root . '/sub');
        },
      ],

      'legacy hard positional is ignored' => [
        [],
        NULL,
        [
          '* [INFO] Started reset.',
          '* [ OK ] Finished reset.',
          '! [TASK] Changing permissions',
          '! [TASK] Resetting repository files.',
          '! [TASK] Removing all untracked files.',
          '! [TASK] Removing empty directories.',
        ],
        ['reset', 'hard'],
      ],

      'hard' => [
        [],
        static function (self $test, string $root): void {
          static::createFileTree($root, [
            'vendor/composer.json' => 'x',
            'web/themes/custom/t1/build/script.js' => 'x',
            'sub/node_modules/pkg/index.js' => 'x',
            'ignored.txt' => 'x',
            'empty_dir' => NULL,
            '.git/refs' => NULL,
          ]);
          $test->mockShellExec("ignored.txt\0");
          $test->mockPassthru(['cmd' => 'git reset --hard', 'result_code' => 0]);
          $test->mockPassthru(['cmd' => 'git clean -f -d', 'result_code' => 0]);
        },
        [
          '* [INFO] Started reset.',
          '* [TASK] Changing permissions and removing all other untracked files.',
          '* [TASK] Resetting repository files.',
          '* [TASK] Removing all untracked files.',
          '* [TASK] Removing empty directories.',
          '* [ OK ] Finished reset.',
        ],
        ['reset', '--hard'],
        FALSE,
        static function (self $test, string $root): void {
          $test->assertDirectoryDoesNotExist($root . '/vendor');
          $test->assertFileDoesNotExist($root . '/ignored.txt');
          $test->assertDirectoryDoesNotExist($root . '/empty_dir');
          // Directories that became empty are swept recursively.
          $test->assertDirectoryDoesNotExist($root . '/sub');
          $test->assertDirectoryDoesNotExist($root . '/web');
          // Empty directories inside '.git' survive the sweep.
          $test->assertDirectoryExists($root . '/.git/refs');
        },
      ],

      'hard git reset fails' => [
        [],
        static function (self $test, string $root): void {
          $test->mockShellExec('');
          $test->mockPassthru(['cmd' => 'git reset --hard', 'result_code' => 1]);
        },
        [
          '* [INFO] Started reset.',
          '* [TASK] Resetting repository files.',
          '! [ OK ] Finished reset.',
        ],
        ['reset', '--hard'],
        TRUE,
      ],

      'hard git clean fails' => [
        [],
        static function (self $test, string $root): void {
          $test->mockShellExec('');
          $test->mockPassthru(['cmd' => 'git reset --hard', 'result_code' => 0]);
          $test->mockPassthru(['cmd' => 'git clean -f -d', 'result_code' => 1]);
        },
        [
          '* [INFO] Started reset.',
          '* [TASK] Removing all untracked files.',
          '! [ OK ] Finished reset.',
        ],
        ['reset', '--hard'],
        TRUE,
      ],

      'custom webroot' => [
        ['WEBROOT' => 'docroot'],
        static function (self $test, string $root): void {
          static::createFileTree($root, [
            'docroot/core/lib/core.txt' => 'x',
            'docroot/themes/custom/t1/build/script.js' => 'x',
          ]);
        },
        [
          '* [INFO] Started reset.',
          '* [ OK ] Finished reset.',
        ],
        NULL,
        FALSE,
        static function (self $test, string $root): void {
          $test->assertDirectoryDoesNotExist($root . '/docroot/core');
          $test->assertDirectoryDoesNotExist($root . '/docroot/themes/custom/t1/build');
        },
      ],
    ];
  }

  protected static function createFileTree(string $root, array $items): void {
    foreach ($items as $path => $content) {
      $full = $root . '/' . $path;

      if ($content === NULL) {
        mkdir($full, 0755, TRUE);
        continue;
      }

      $dir = dirname($full);
      if (!is_dir($dir)) {
        mkdir($dir, 0755, TRUE);
      }

      file_put_contents($full, $content);
    }
  }

}
