<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for reset script.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class ResetTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('WEBROOT', 'web');
  }

  #[DataProvider('dataProviderReset')]
  public function testReset(array $env_vars, array $mocks, array $expected, ?array $argv = NULL, bool $expect_error = FALSE): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    if ($argv !== NULL) {
      $GLOBALS['argv'] = $argv;
    }

    foreach ($mocks as $mock) {
      $this->mockPassthru($mock);
    }

    if ($expect_error) {
      try {
        $this->runScript('src/reset', 1);
      }
      catch (QuitErrorException $e) {
        if (!empty($expected)) {
          $this->assertStringContainsOrNot($e->getOutput(), $expected);
        }
        throw $e;
      }
      return;
    }

    $output = $this->runScript('src/reset');

    $this->assertStringContainsOrNot($output, $expected);
  }

  public static function dataProviderReset(): array {
    $rm_web = 'rm -rf ./vendor ./web/core ./web/profiles/contrib ./web/modules/contrib ./web/themes/contrib ./web/themes/custom/*/build ./web/themes/custom/*/scss/_components.scss';
    $rm_docroot = 'rm -rf ./vendor ./docroot/core ./docroot/profiles/contrib ./docroot/modules/contrib ./docroot/themes/contrib ./docroot/themes/custom/*/build ./docroot/themes/custom/*/scss/_components.scss';
    $rm_node = 'find . -type d -name node_modules | xargs rm -Rf';
    $git_chmod = 'git ls-files --others -i --exclude-from=.gitignore -z | while IFS= read -r -d "" file; do chmod 777 "$file" 2>/dev/null; rm -rf "$file" 2>/dev/null; done';
    $git_reset = 'git reset --hard';
    $git_clean = 'git clean -f -d';
    $find_empty = 'find . -type d -not -path "./.git/*" -empty -delete';

    return [
      'soft default' => [
        [],
        [
          ['cmd' => $rm_web, 'result_code' => 0],
          ['cmd' => $rm_node, 'result_code' => 0],
        ],
        [
          '* [INFO] Started reset.',
          '* [ OK ] Finished reset.',
          '! [TASK] Changing permissions',
          '! [TASK] Resetting repository files.',
          '! [TASK] Removing all untracked files.',
          '! [TASK] Removing empty directories.',
        ],
      ],

      'hard' => [
        [],
        [
          ['cmd' => $rm_web, 'result_code' => 0],
          ['cmd' => $rm_node, 'result_code' => 0],
          ['cmd' => $git_chmod, 'result_code' => 0],
          ['cmd' => $git_reset, 'result_code' => 0],
          ['cmd' => $git_clean, 'result_code' => 0],
          ['cmd' => $find_empty, 'result_code' => 0],
        ],
        [
          '* [INFO] Started reset.',
          '* [TASK] Changing permissions and remove all other untracked files.',
          '* [TASK] Resetting repository files.',
          '* [TASK] Removing all untracked files.',
          '* [TASK] Removing empty directories.',
          '* [ OK ] Finished reset.',
        ],
        ['reset', 'hard'],
      ],

      'hard git reset fails' => [
        [],
        [
          ['cmd' => $rm_web, 'result_code' => 0],
          ['cmd' => $rm_node, 'result_code' => 0],
          ['cmd' => $git_chmod, 'result_code' => 0],
          ['cmd' => $git_reset, 'result_code' => 1],
        ],
        [
          '* [INFO] Started reset.',
          '* [TASK] Resetting repository files.',
          '! [ OK ] Finished reset.',
        ],
        ['reset', 'hard'],
        TRUE,
      ],

      'hard git clean fails' => [
        [],
        [
          ['cmd' => $rm_web, 'result_code' => 0],
          ['cmd' => $rm_node, 'result_code' => 0],
          ['cmd' => $git_chmod, 'result_code' => 0],
          ['cmd' => $git_reset, 'result_code' => 0],
          ['cmd' => $git_clean, 'result_code' => 1],
        ],
        [
          '* [INFO] Started reset.',
          '* [TASK] Removing all untracked files.',
          '! [ OK ] Finished reset.',
        ],
        ['reset', 'hard'],
        TRUE,
      ],

      'custom webroot' => [
        ['WEBROOT' => 'docroot'],
        [
          ['cmd' => $rm_docroot, 'result_code' => 0],
          ['cmd' => $rm_node, 'result_code' => 0],
        ],
        [
          '* [INFO] Started reset.',
          '* [ OK ] Finished reset.',
        ],
      ],
    ];
  }

}
