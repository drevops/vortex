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
    $this->processRun('ahoy --version');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputNotContains('[fatal]');

    $this->logSubstep('Assert calling local commands with local file path specified and file is present works correctly');
    File::copy('.ahoy.local.example.yml', '.ahoy.local.yml');
    $this->processRun('ahoy local help');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Custom local commands');
    $this->assertProcessOutputNotContains('[fatal]');

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

    $this->processRun('ahoy local mylocalcommand');
    $this->assertProcessFailed();
    $this->assertProcessOutputContains('expected failure');
    $this->assertProcessOutputNotContains('[fatal]');
  }

  /**
   * Test doctor info command.
   */
  public function testDoctorInfo(): void {
    $this->logSubstep('Run ahoy doctor info');
    $this->processRun('ahoy doctor info');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('System information report');
    $this->assertProcessOutputContains('OPERATING SYSTEM');
    $this->assertProcessOutputContains('DOCKER');
    $this->assertProcessOutputContains('DOCKER COMPOSE');
    $this->assertProcessOutputContains('PYGMY');
    $this->assertProcessOutputContains('AHOY');
  }

}
