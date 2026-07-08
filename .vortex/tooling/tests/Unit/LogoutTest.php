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
  public function testLogout(array $env_vars, array $shell_mocks, array $mocks, array $expected): void {
    if (!empty($env_vars)) {
      $this->envSetMultiple($env_vars);
    }

    if (!empty($shell_mocks)) {
      $this->mockShellExecMultiple(array_map(static fn(string $value): array => ['value' => $value], $shell_mocks));
    }

    foreach ($mocks as $mock) {
      $this->mockPassthru($mock);
    }

    $output = $this->runScript('src/vortex-logout');

    if (empty($expected)) {
      $this->assertEmpty(trim($output));
      return;
    }

    $this->assertStringContainsOrNot($output, $expected);
  }

  public function testLogoutBlockAdminFails(): void {
    $this->envSet('VORTEX_LOGOUT_BLOCK_ADMIN', '1');

    $this->mockShellExec("admin\n");
    $this->mockPassthru(['cmd' => "./vendor/bin/drush -y -- user:block 'admin'", 'result_code' => 1]);

    $this->runScriptError('src/vortex-logout', 'Failed to block admin user.');
  }

  public static function dataProviderLogout(): array {
    $block_cmd = "./vendor/bin/drush -y -- user:block 'admin'";

    return [
      'block admin' => [
        ['VORTEX_LOGOUT_BLOCK_ADMIN' => '1'],
        ["admin\n"],
        [['cmd' => $block_cmd, 'output' => 'Blocked user: admin', 'result_code' => 0]],
        ['* Blocked user: admin'],
      ],

      'block admin with multiline query result' => [
        ['VORTEX_LOGOUT_BLOCK_ADMIN' => '1'],
        ["admin\nextra_row\n"],
        [['cmd' => $block_cmd, 'output' => 'Blocked user: admin', 'result_code' => 0]],
        ['* Blocked user: admin'],
      ],

      'block admin with empty admin name' => [
        ['VORTEX_LOGOUT_BLOCK_ADMIN' => '1'],
        ["\n"],
        [],
        [],
      ],

      'without block admin' => [
        ['VORTEX_LOGOUT_BLOCK_ADMIN' => '0'],
        [],
        [],
        [],
      ],

      'fallback variable' => [
        ['VORTEX_UNBLOCK_ADMIN' => '0'],
        [],
        [],
        [],
      ],

      'default blocks admin' => [
        [],
        ["admin\n"],
        [['cmd' => $block_cmd, 'output' => 'Blocked user: admin', 'result_code' => 0]],
        ['* Blocked user: admin'],
      ],
    ];
  }

}
