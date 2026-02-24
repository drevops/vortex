<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for logout script.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class LogoutTest extends UnitTestCase {

  #[DataProvider('dataProviderLogout')]
  public function testLogout(array $env_vars, array $mocks, array $expected): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    foreach ($mocks as $mock) {
      $this->mockPassthru($mock);
    }

    $output = $this->runScript('src/logout');

    if (empty($expected)) {
      $this->assertEmpty(trim($output));
      return;
    }

    $this->assertStringContainsOrNot($output, $expected);
  }

  public function testLogoutBlockAdminFails(): void {
    $this->envSet('VORTEX_LOGOUT_BLOCK_ADMIN', '1');

    $block_cmd = './vendor/bin/drush -y sql:query "SELECT name FROM `users_field_data` WHERE `uid` = \'1\';" | head -n 1 | xargs ./vendor/bin/drush -y -- user:block';

    $this->mockPassthru(['cmd' => $block_cmd, 'result_code' => 1]);

    $this->runScriptError('src/logout', 'Failed to block admin user.');
  }

  public static function dataProviderLogout(): array {
    $block_cmd = './vendor/bin/drush -y sql:query "SELECT name FROM `users_field_data` WHERE `uid` = \'1\';" | head -n 1 | xargs ./vendor/bin/drush -y -- user:block';

    return [
      'block admin' => [
        ['VORTEX_LOGOUT_BLOCK_ADMIN' => '1'],
        [['cmd' => $block_cmd, 'output' => 'Blocked user: admin', 'result_code' => 0]],
        ['* Blocked user: admin'],
      ],

      'without block admin' => [
        ['VORTEX_LOGOUT_BLOCK_ADMIN' => '0'],
        [],
        [],
      ],

      'fallback variable' => [
        ['VORTEX_UNBLOCK_ADMIN' => '0'],
        [],
        [],
      ],

      'default blocks admin' => [
        [],
        [['cmd' => $block_cmd, 'output' => 'Blocked user: admin', 'result_code' => 0]],
        ['* Blocked user: admin'],
      ],
    ];
  }

}
