<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for login script.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class LoginTest extends UnitTestCase {

  #[DataProvider('dataProviderLogin')]
  public function testLogin(array $env_vars, array $shell_mocks, array $mocks, array $expected, bool $expect_error = FALSE): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    if (!empty($shell_mocks)) {
      $this->mockShellExecMultiple(array_map(static fn(string $value): array => ['value' => $value], $shell_mocks));
    }

    foreach ($mocks as $mock) {
      $this->mockPassthru($mock);
    }

    if ($expect_error) {
      try {
        $this->runScript('src/vortex-login', 1);
      }
      catch (QuitErrorException $e) {
        if (!empty($expected)) {
          $this->assertStringContainsOrNot($e->getOutput(), $expected);
        }
        throw $e;
      }
      return;
    }

    $output = $this->runScript('src/vortex-login');

    if (empty($expected)) {
      $this->assertEmpty(trim($output));
      return;
    }

    $this->assertStringContainsOrNot($output, $expected);
  }

  public static function dataProviderLogin(): array {
    $modules_without_policy = "admin_toolbar\nviews";
    $modules_with_policy = "admin_toolbar\npassword_policy\nviews";
    $admin_query_output = "admin\n";
    $password_reset_cmd = './vendor/bin/drush -y sql:query \'UPDATE `user__field_password_expiration` SET `field_password_expiration_value` = 0 WHERE `bundle` = "user" AND `entity_id` = 1;\' >/dev/null';
    $unblock_cmd = "./vendor/bin/drush -y -- user:unblock 'admin' 2>/dev/null";
    $login_cmd = './vendor/bin/drush -y user:login';
    $login_url = 'http://example.com/user/reset/1/abc123/login';

    return [
      'unblock admin' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '1'],
        [$modules_without_policy, $admin_query_output],
        [
          ['cmd' => $unblock_cmd, 'result_code' => 0],
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'unblock admin and password policy' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '1'],
        [$modules_with_policy, $admin_query_output],
        [
          ['cmd' => $password_reset_cmd, 'result_code' => 0],
          ['cmd' => $unblock_cmd, 'result_code' => 0],
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'unblock admin with multiline query result' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '1'],
        [$modules_without_policy, "admin\nextra_row\n"],
        [
          ['cmd' => $unblock_cmd, 'result_code' => 0],
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'unblock admin with empty admin name' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '1'],
        [$modules_without_policy, "\n"],
        [
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'without unblock admin' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '0'],
        [],
        [
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'fallback variable' => [
        ['VORTEX_UNBLOCK_ADMIN' => '0'],
        [],
        [
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'login failure' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '0'],
        [],
        [
          ['cmd' => $login_cmd, 'result_code' => 1],
        ],
        [],
        TRUE,
      ],

      'default unblocks admin' => [
        [],
        [$modules_without_policy, $admin_query_output],
        [
          ['cmd' => $unblock_cmd, 'result_code' => 0],
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],
    ];
  }

}
