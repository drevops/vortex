<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for update-vortex script.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class UpdateVortexTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    $this->envSetMultiple([
      'VORTEX_INSTALLER_TEMPLATE_REPO' => 'https://github.com/drevops/vortex.git#stable',
      'VORTEX_INSTALLER_URL' => 'https://www.vortextemplate.com/install',
      'VORTEX_INSTALLER_URL_CACHE_BUST' => '1234567890',
      'VORTEX_INSTALLER_PATH' => '',
      'VORTEX_INSTALLER_INTERACTIVE' => '0',
    ]);
  }

  #[DataProvider('dataProviderUpdateVortex')]
  public function testUpdateVortex(array $env_vars, array $mocks, array $expected, ?array $argv = NULL, bool $expect_error = FALSE, array $create_files = []): void {
    $tmp = self::$tmp;
    $replace_tmp = function (&$value) use ($tmp): void {
      if (is_string($value)) {
        $value = str_replace('__TMP__', $tmp, $value);
      }
    };

    foreach ($create_files as $path => $content) {
      file_put_contents(str_replace('__TMP__', $tmp, $path), $content);
    }

    array_walk_recursive($env_vars, $replace_tmp);
    array_walk_recursive($mocks, $replace_tmp);
    array_walk_recursive($expected, $replace_tmp);

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
        $this->runScript('src/update-vortex', 1);
      }
      catch (QuitErrorException $e) {
        if (!empty($expected)) {
          $this->assertStringContainsOrNot($e->getOutput(), $expected);
        }
        throw $e;
      }
      return;
    }

    try {
      $this->runScript('src/update-vortex', 0);
    }
    catch (QuitSuccessException $e) {
      if (!empty($expected)) {
        $this->assertStringContainsOrNot($e->getOutput(), $expected);
      }
      throw $e;
    }
  }

  public static function dataProviderUpdateVortex(): array {
    $curl_cmd = "curl -fsSL 'https://www.vortextemplate.com/install?1234567890' -o 'installer.php'";
    $default_repo = 'https://github.com/drevops/vortex.git#stable';

    return [
      'download and run' => [
        [],
        [
          ['cmd' => $curl_cmd, 'result_code' => 0],
          ['cmd' => "php 'installer.php' --no-interaction --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* Using installer script from URL: https://www.vortextemplate.com/install',
          '* Downloading installer to installer.php',
          '! Using installer script from local path',
        ],
      ],

      'local installer path' => [
        ['VORTEX_INSTALLER_PATH' => '__TMP__/my-installer.php'],
        [
          ['cmd' => "php '__TMP__/my-installer.php' --no-interaction --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* Using installer script from local path: __TMP__/my-installer.php',
          '! Downloading installer',
        ],
        NULL,
        FALSE,
        ['__TMP__/my-installer.php' => '<?php echo "installed";'],
      ],

      'local installer not found' => [
        ['VORTEX_INSTALLER_PATH' => '/nonexistent/installer.php'],
        [],
        ['* [FAIL] Installer script not found at /nonexistent/installer.php'],
        NULL,
        TRUE,
      ],

      'download failure' => [
        [],
        [
          ['cmd' => $curl_cmd, 'result_code' => 1],
        ],
        ['* [FAIL] Failed to download installer from https://www.vortextemplate.com/install'],
        NULL,
        TRUE,
      ],

      'interactive mode' => [
        ['VORTEX_INSTALLER_INTERACTIVE' => '1'],
        [
          ['cmd' => $curl_cmd, 'result_code' => 0],
          ['cmd' => "php 'installer.php' --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* Using installer script from URL:',
          '* Downloading installer to installer.php',
        ],
      ],

      'interactive via argument' => [
        [],
        [
          ['cmd' => $curl_cmd, 'result_code' => 0],
          ['cmd' => "php 'installer.php' --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* Using installer script from URL:',
          '* Downloading installer to installer.php',
        ],
        ['update-vortex', '--interactive'],
      ],

      'custom repo via argument' => [
        [],
        [
          ['cmd' => $curl_cmd, 'result_code' => 0],
          ['cmd' => "php 'installer.php' --no-interaction --uri='file:///local/path/to/vortex.git#1.2.3'", 'result_code' => 0],
        ],
        [
          '* Using installer script from URL:',
          '* Downloading installer to installer.php',
        ],
        ['update-vortex', 'file:///local/path/to/vortex.git#1.2.3'],
      ],

      'local path repo via argument' => [
        [],
        [
          ['cmd' => $curl_cmd, 'result_code' => 0],
          ['cmd' => "php 'installer.php' --no-interaction --uri='/local/path/to/vortex#stable'", 'result_code' => 0],
        ],
        [
          '* Using installer script from URL:',
          '* Downloading installer to installer.php',
        ],
        ['update-vortex', '/local/path/to/vortex#stable'],
      ],

      'git ssh url via argument' => [
        [],
        [
          ['cmd' => $curl_cmd, 'result_code' => 0],
          ['cmd' => "php 'installer.php' --no-interaction --uri='git@github.com:drevops/vortex.git#v1.2.3'", 'result_code' => 0],
        ],
        [
          '* Using installer script from URL:',
          '* Downloading installer to installer.php',
        ],
        ['update-vortex', 'git@github.com:drevops/vortex.git#v1.2.3'],
      ],

      'interactive with custom repo' => [
        [],
        [
          ['cmd' => $curl_cmd, 'result_code' => 0],
          ['cmd' => "php 'installer.php' --uri='https://github.com/custom/repo.git#main'", 'result_code' => 0],
        ],
        [
          '* Using installer script from URL:',
          '* Downloading installer to installer.php',
        ],
        ['update-vortex', '--interactive', 'https://github.com/custom/repo.git#main'],
      ],

      'installer fails' => [
        [],
        [
          ['cmd' => $curl_cmd, 'result_code' => 0],
          ['cmd' => "php 'installer.php' --no-interaction --uri='" . $default_repo . "'", 'result_code' => 1],
        ],
        [],
        NULL,
        TRUE,
      ],
    ];
  }

}
