<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;

/**
 * Tests workflow utilities.
 */
class WorkflowUtilitiesTest extends FunctionalTestCase {

  /**
   * Test local Ahoy commands functionality.
   */
  public function testLocalAhoyCommands(): void {
    $this->logSubstep('Assert calling local commands without local file does not throw error');
    $this->cmd('ahoy --version', '! [fatal]');

    $this->logSubstep('Assert calling local commands with local file path specified and file is present works correctly');
    File::copy('.ahoy.local.example.yml', '.ahoy.local.yml');
    $this->cmd('ahoy local help', ['* Custom local commands', '! [fatal]']);

    $this->logSubstep('Assert calling local commands with local file path specified and file is present and file return non-zero exit code');
    $local_command_content = <<<YAML

  mylocalcommand:
    cmd: |
      echo 'expected failure'
      exit 1
YAML;
    $existing_content = File::read('.ahoy.local.yml');
    $this->assertNotEmpty($existing_content, 'Failed to read .ahoy.local.yml');

    File::dump('.ahoy.local.yml', $existing_content . $local_command_content);

    $this->cmdFail('ahoy local mylocalcommand', ['* expected failure', '! [fatal]']);
  }

  /**
   * Test doctor info command.
   */
  public function testDoctorInfo(): void {
    $this->logSubstep('Run ahoy doctor info');
    $this->cmd('ahoy doctor info', [
      'System information report',
      'OPERATING SYSTEM',
      'DOCKER',
      'DOCKER COMPOSE',
      'PYGMY',
      'AHOY',
    ]);
  }

}
