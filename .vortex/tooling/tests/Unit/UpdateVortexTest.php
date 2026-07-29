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

    $this->envSetMultiple([
      'VORTEX_CLI_INSTALL_TEMPLATE_REPO' => 'https://github.com/drevops/vortex.git#stable',
      'VORTEX_CLI_URL' => 'https://www.vortextemplate.com/install',
      'VORTEX_CLI_URL_CACHE_BUST' => '1234567890',
      'VORTEX_CLI_PATH' => '',
      'VORTEX_CLI_INSTALL_INTERACTIVE' => '0',
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

    // Mock fopen to prevent request()'s save_to from creating files in the
    // working directory (src/) via CURLOPT_FILE streaming.
    $fopen_mock = $this->getFunctionMock('DrevOps\\VortexTooling', 'fopen');
    $fopen_mock->expects($this->any())->willReturnCallback(fn() => \fopen('php://memory', 'w'));

    foreach ($mocks as $mock) {
      if (isset($mock['request'])) {
        $this->mockRequestMultiple([$mock['request']]);
      }
      else {
        $this->mockPassthru($mock);
      }
    }

    if ($expect_error) {
      try {
        $this->runScript('src/vortex-update', 1);
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
      $this->runScript('src/vortex-update', 0);
    }
    catch (QuitSuccessException $e) {
      if (!empty($expected)) {
        $this->assertStringContainsOrNot($e->getOutput(), $expected);
      }
      throw $e;
    }
  }

  public static function dataProviderUpdateVortex(): array {
    $download_request = fn(bool $ok = TRUE): array => ['request' => ['url' => 'https://www.vortextemplate.com/install?1234567890', 'method' => 'GET', 'response' => $ok ? ['body' => '<?php echo "vortex";'] : ['ok' => FALSE, 'status' => 500, 'body' => '']]];
    $default_repo = 'https://github.com/drevops/vortex.git#stable';

    return [
      'download and run' => [
        [],
        [
          $download_request(),
          ['cmd' => "php 'vortex.phar' --no-interaction --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* Using Vortex CLI from URL: https://www.vortextemplate.com/install',
          '* Downloading Vortex CLI to vortex.phar',
          '! Using Vortex CLI from local path',
        ],
      ],

      'local CLI path' => [
        ['VORTEX_CLI_PATH' => '__TMP__/my-vortex.phar'],
        [
          ['cmd' => "php '__TMP__/my-vortex.phar' --no-interaction --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* Using Vortex CLI from local path: __TMP__/my-vortex.phar',
          '! Downloading Vortex CLI',
        ],
        NULL,
        FALSE,
        ['__TMP__/my-vortex.phar' => '<?php echo "installed";'],
      ],

      'superseded local CLI path still resolves and warns' => [
        ['VORTEX_CLI_PATH' => '', 'VORTEX_INSTALLER_PATH' => '__TMP__/my-vortex.phar'],
        [
          ['cmd' => "php '__TMP__/my-vortex.phar' --no-interaction --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* VORTEX_INSTALLER_PATH is deprecated and will be removed in a future release. Use VORTEX_CLI_PATH instead.',
          '* Using Vortex CLI from local path: __TMP__/my-vortex.phar',
          '! Downloading Vortex CLI',
        ],
        NULL,
        FALSE,
        ['__TMP__/my-vortex.phar' => '<?php echo "installed";'],
      ],

      'local CLI not found' => [
        ['VORTEX_CLI_PATH' => '/nonexistent/vortex.phar'],
        [],
        ['* [FAIL] Vortex CLI not found at /nonexistent/vortex.phar'],
        NULL,
        TRUE,
      ],

      'download failure' => [
        [],
        [
          $download_request(FALSE),
        ],
        ['* [FAIL] Failed to download Vortex CLI from https://www.vortextemplate.com/install'],
        NULL,
        TRUE,
      ],

      'interactive mode' => [
        ['VORTEX_CLI_INSTALL_INTERACTIVE' => '1'],
        [
          $download_request(),
          ['cmd' => "php 'vortex.phar' --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* Using Vortex CLI from URL:',
          '* Downloading Vortex CLI to vortex.phar',
        ],
      ],

      'interactive via argument' => [
        [],
        [
          $download_request(),
          ['cmd' => "php 'vortex.phar' --uri='" . $default_repo . "'", 'result_code' => 0],
        ],
        [
          '* Using Vortex CLI from URL:',
          '* Downloading Vortex CLI to vortex.phar',
        ],
        ['update-vortex', '--interactive'],
      ],

      'custom repo via argument' => [
        [],
        [
          $download_request(),
          ['cmd' => "php 'vortex.phar' --no-interaction --uri='file:///local/path/to/vortex.git#1.2.3'", 'result_code' => 0],
        ],
        [
          '* Using Vortex CLI from URL:',
          '* Downloading Vortex CLI to vortex.phar',
        ],
        ['update-vortex', 'file:///local/path/to/vortex.git#1.2.3'],
      ],

      'local path repo via argument' => [
        [],
        [
          $download_request(),
          ['cmd' => "php 'vortex.phar' --no-interaction --uri='/local/path/to/vortex#stable'", 'result_code' => 0],
        ],
        [
          '* Using Vortex CLI from URL:',
          '* Downloading Vortex CLI to vortex.phar',
        ],
        ['update-vortex', '/local/path/to/vortex#stable'],
      ],

      'git ssh url via argument' => [
        [],
        [
          $download_request(),
          ['cmd' => "php 'vortex.phar' --no-interaction --uri='git@github.com:drevops/vortex.git#v1.2.3'", 'result_code' => 0],
        ],
        [
          '* Using Vortex CLI from URL:',
          '* Downloading Vortex CLI to vortex.phar',
        ],
        ['update-vortex', 'git@github.com:drevops/vortex.git#v1.2.3'],
      ],

      'interactive with custom repo' => [
        [],
        [
          $download_request(),
          ['cmd' => "php 'vortex.phar' --uri='https://github.com/custom/repo.git#main'", 'result_code' => 0],
        ],
        [
          '* Using Vortex CLI from URL:',
          '* Downloading Vortex CLI to vortex.phar',
        ],
        ['update-vortex', '--interactive', 'https://github.com/custom/repo.git#main'],
      ],

      'CLI fails' => [
        [],
        [
          $download_request(),
          ['cmd' => "php 'vortex.phar' --no-interaction --uri='" . $default_repo . "'", 'result_code' => 1],
        ],
        [],
        NULL,
        TRUE,
      ],
    ];
  }

}
