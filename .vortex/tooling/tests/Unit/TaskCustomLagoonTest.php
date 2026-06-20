<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;

#[Group('scripts')]
class TaskCustomLagoonTest extends UnitTestCase {

  /**
   * Path to the src directory.
   */
  protected static string $srcDir;

  protected function setUp(): void {
    parent::setUp();

    self::$srcDir = (string) realpath(__DIR__ . '/../../src');

    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_NAME', 'Test task');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_PROJECT', 'myproject');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_BRANCH', 'main');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_COMMAND', 'drush cr');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_INSTANCE', 'amazeeio');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_GRAPHQL', 'https://api.lagoon.amazeeio.cloud/graphql');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_HOSTNAME', 'ssh.lagoon.amazeeio.cloud');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_INSTANCE_PORT', '32222');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_SSH_FILE', '/home/user/.ssh/id_rsa');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_CLI_FORCE_INSTALL', '1');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_CLI_PATH', self::$tmp . '/lagoon-cli');
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_CLI_VERSION', 'v0.32.0');
  }

  public function testMissingBranch(): void {
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_BRANCH', '');

    $this->runScriptError('src/task-custom-lagoon', 'Missing required value for VORTEX_TASK_CUSTOM_LAGOON_BRANCH');
  }

  public function testMissingCommand(): void {
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_COMMAND', '');

    $this->runScriptError('src/task-custom-lagoon', 'Missing required value for VORTEX_TASK_CUSTOM_LAGOON_COMMAND');
  }

  public function testMissingProject(): void {
    $this->envSet('VORTEX_TASK_CUSTOM_LAGOON_PROJECT', '');
    $this->envUnset('LAGOON_PROJECT');

    $this->runScriptError('src/task-custom-lagoon', 'Missing required value for VORTEX_TASK_CUSTOM_LAGOON_PROJECT');
  }

  public function testSuccess(): void {
    $cli_path = self::$tmp . '/lagoon-cli';

    $platform = strtolower(php_uname('s'));
    $arch = str_replace(['x86_64', 'aarch64'], ['amd64', 'arm64'], php_uname('m'));
    $download_url = sprintf(
      'https://github.com/uselagoon/lagoon-cli/releases/download/v0.32.0/lagoon-cli-v0.32.0-%s-%s',
      $platform,
      $arch
    );

    $lagoon_bin = $cli_path . '/lagoon';

    // Mock download of Lagoon CLI.
    $this->mockRequestGet($download_url, [], 60, ['body' => 'lagoon-binary-content']);

    $this->mockPassthruMultiple([
      // setup-ssh.
      [
        'cmd' => self::$srcDir . '/setup-ssh',
        'result_code' => 0,
      ],
      // Lagoon config add.
      [
        'cmd' => sprintf(
          '%s config add --force -l %s -g %s -H %s -P %s',
          escapeshellarg($lagoon_bin),
          escapeshellarg('amazeeio'),
          escapeshellarg('https://api.lagoon.amazeeio.cloud/graphql'),
          escapeshellarg('ssh.lagoon.amazeeio.cloud'),
          escapeshellarg('32222')
        ),
        'result_code' => 0,
      ],
      // Lagoon run custom.
      [
        'cmd' => sprintf(
          '%s --force --skip-update-check -i %s -l %s -p %s run custom -e %s -N %s -c %s',
          escapeshellarg($lagoon_bin),
          escapeshellarg('/home/user/.ssh/id_rsa'),
          escapeshellarg('amazeeio'),
          escapeshellarg('myproject'),
          escapeshellarg('main'),
          escapeshellarg('Test task'),
          escapeshellarg('drush cr')
        ),
        'result_code' => 0,
      ],
    ]);

    $output = $this->runScript('src/task-custom-lagoon');

    $this->assertStringContainsString('Started Lagoon task Test task.', $output);
    $this->assertStringContainsString('Installing Lagoon CLI.', $output);
    $this->assertStringContainsString('Configuring Lagoon instance.', $output);
    $this->assertStringContainsString('Creating Test task task: project myproject, branch: main.', $output);
    $this->assertStringContainsString('Finished Lagoon task Test task.', $output);
  }

  public function testSetupSshFails(): void {
    $this->mockPassthru([
      'cmd' => self::$srcDir . '/setup-ssh',
      'result_code' => 1,
    ]);

    $this->runScriptError('src/task-custom-lagoon', 'Failed to setup SSH');
  }

  public function testLagoonRunFails(): void {
    $cli_path = self::$tmp . '/lagoon-cli';

    $platform = strtolower(php_uname('s'));
    $arch = str_replace(['x86_64', 'aarch64'], ['amd64', 'arm64'], php_uname('m'));
    $download_url = sprintf(
      'https://github.com/uselagoon/lagoon-cli/releases/download/v0.32.0/lagoon-cli-v0.32.0-%s-%s',
      $platform,
      $arch
    );

    $lagoon_bin = $cli_path . '/lagoon';

    $this->mockRequestGet($download_url, [], 60, ['body' => 'lagoon-binary-content']);

    $this->mockPassthruMultiple([
      [
        'cmd' => self::$srcDir . '/setup-ssh',
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          '%s config add --force -l %s -g %s -H %s -P %s',
          escapeshellarg($lagoon_bin),
          escapeshellarg('amazeeio'),
          escapeshellarg('https://api.lagoon.amazeeio.cloud/graphql'),
          escapeshellarg('ssh.lagoon.amazeeio.cloud'),
          escapeshellarg('32222')
        ),
        'result_code' => 0,
      ],
      [
        'cmd' => sprintf(
          '%s --force --skip-update-check -i %s -l %s -p %s run custom -e %s -N %s -c %s',
          escapeshellarg($lagoon_bin),
          escapeshellarg('/home/user/.ssh/id_rsa'),
          escapeshellarg('amazeeio'),
          escapeshellarg('myproject'),
          escapeshellarg('main'),
          escapeshellarg('Test task'),
          escapeshellarg('drush cr')
        ),
        'result_code' => 1,
      ],
    ]);

    $this->runScriptError('src/task-custom-lagoon', 'Failed to run Lagoon custom task');
  }

}
