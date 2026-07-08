<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class TaskCustomLagoonTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  /**
   * SSH file used in command assertions.
   */
  protected static string $sshFile = '/home/user/.ssh/id_rsa';

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    // Report the 'lagoon' CLI as present so no download is attempted.
    $this->mockCommandExists();

    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_NAME', 'Test task');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_PROJECT', 'myproject');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_BRANCH', 'main');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_COMMAND', 'drush cr');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_INSTANCE', 'amazeeio');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_GRAPHQL', 'https://api.lagoon.amazeeio.cloud/graphql');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_HOSTNAME', 'ssh.lagoon.amazeeio.cloud');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_PORT', '32222');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_SSH_FILE', self::$sshFile);
    $this->envSet('VORTEX_LAGOONCLI_PATH', self::$tmp);
  }

  public function testMissingBranch(): void {
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_BRANCH', '');

    $this->runScriptError('src/vortex-task-custom-lagoon', 'Missing required value for VORTEX_TASK_CUSTOM_LAGOON_BRANCH');
  }

  public function testMissingCommand(): void {
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_COMMAND', '');

    $this->runScriptError('src/vortex-task-custom-lagoon', 'Missing required value for VORTEX_TASK_CUSTOM_LAGOON_COMMAND');
  }

  public function testMissingProject(): void {
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_PROJECT', '');
    $this->envUnset('LAGOON_PROJECT');

    $this->runScriptError('src/vortex-task-custom-lagoon', 'Missing required value for VORTEX_TASK_CUSTOM_LAGOON_PROJECT');
  }

  public function testSuccess(): void {
    $this->mockPassthruMultiple([
      // setup-ssh.
      [
        'cmd' => self::$srcDir . '/vortex-setup-ssh',
        'result_code' => 0,
      ],
      // Lagoon config add.
      [
        'cmd' => $this->lagoonConfigAddCmd(),
        'result_code' => 0,
      ],
      // Lagoon run custom.
      [
        'cmd' => $this->lagoonRunCustomCmd(),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/vortex-task-custom-lagoon');

    $this->assertStringContainsString('Started Lagoon task Test task.', $output);
    $this->assertStringContainsString('Using the Lagoon CLI found on PATH.', $output);
    $this->assertStringContainsString('Configured Lagoon instance.', $output);
    $this->assertStringContainsString('Creating Test task task: project myproject, branch: main.', $output);
    $this->assertStringContainsString('Created Test task task.', $output);
    $this->assertStringContainsString('Finished Lagoon task Test task.', $output);
  }

  public function testSetupSshFails(): void {
    $this->mockPassthru([
      'cmd' => self::$srcDir . '/vortex-setup-ssh',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/vortex-task-custom-lagoon', 'Failed to setup SSH');
  }

  public function testLagoonRunFails(): void {
    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/vortex-setup-ssh',
        'result_code' => 0,
      ],
      [
        'cmd' => $this->lagoonConfigAddCmd(),
        'result_code' => 0,
      ],
      [
        'cmd' => $this->lagoonRunCustomCmd(),
        'result_code' => 1,
      ],
    ]);

    $this->runScriptError('src/vortex-task-custom-lagoon', 'failed with exit code 1');
  }

  protected function lagoonConfigAddCmd(): string {
    return sprintf("'lagoon' --config-file '%s' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'", $this->lagoonConfigFile());
  }

  protected function lagoonRunCustomCmd(): string {
    return sprintf("'lagoon' --config-file '%s' --force --skip-update-check --ssh-key '%s' --lagoon 'amazeeio' --project 'myproject' run custom --environment 'main' --name 'Test task' --command 'drush cr' 2>&1", $this->lagoonConfigFile(), self::$sshFile);
  }

}
