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

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderLogin')]
  public function testLogin(array $env_vars, array $mocks, array $expected, bool $expect_error = FALSE): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    foreach ($mocks as $mock) {
      $this->mockPassthru($mock);
    }

    if ($expect_error) {
      try {
        $this->runScript('src/login', 1);
      }
      catch (QuitErrorException $e) {
        if (!empty($expected)) {
          $this->assertStringContainsOrNot($e->getOutput(), $expected);
        }
        throw $e;
      }
      return;
    }

    $output = $this->runScript('src/login');

    if (empty($expected)) {
      $this->assertEmpty(trim($output));
      return;
    }

    $this->assertStringContainsOrNot($output, $expected);
  }

  public static function dataProviderLogin(): array {
    $password_policy_cmd = './vendor/bin/drush -y pm:list --status=enabled 2>/dev/null | grep -q password_policy';
    $password_reset_cmd = './vendor/bin/drush -y sql:query \'UPDATE `user__field_password_expiration` SET `field_password_expiration_value` = 0 WHERE `bundle` = "user" AND `entity_id` = 1;\' >/dev/null';
    $unblock_cmd = './vendor/bin/drush -y sql:query "SELECT name FROM `users_field_data` WHERE `uid` = \'1\';" | head -n 1 | xargs ./vendor/bin/drush -y -- user:unblock 2>/dev/null';
    $login_cmd = './vendor/bin/drush -y user:login';
    $login_url = 'http://example.com/user/reset/1/abc123/login';

    return [
      'unblock admin' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '1'],
        [
          ['cmd' => $password_policy_cmd, 'result_code' => 1],
          ['cmd' => $unblock_cmd, 'result_code' => 0],
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'unblock admin and password policy' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '1'],
        [
          ['cmd' => $password_policy_cmd, 'result_code' => 0],
          ['cmd' => $password_reset_cmd, 'result_code' => 0],
          ['cmd' => $unblock_cmd, 'result_code' => 0],
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'without unblock admin' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '0'],
        [
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'fallback variable' => [
        ['VORTEX_UNBLOCK_ADMIN' => '0'],
        [
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],

      'login failure' => [
        ['VORTEX_LOGIN_UNBLOCK_ADMIN' => '0'],
        [
          ['cmd' => $login_cmd, 'result_code' => 1],
        ],
        [],
        TRUE,
      ],

      'default unblocks admin' => [
        [],
        [
          ['cmd' => $password_policy_cmd, 'result_code' => 1],
          ['cmd' => $unblock_cmd, 'result_code' => 0],
          ['cmd' => $login_cmd, 'output' => $login_url, 'result_code' => 0],
        ],
        ['* ' . $login_url],
      ],
    ];
  }

}
