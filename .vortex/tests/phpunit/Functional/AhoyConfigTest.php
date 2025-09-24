<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests local Ahoy workflow functionality.
 */
class AhoyConfigTest extends FunctionalTestCase {

  #[Group('p0')]
  public function testLocalConfigAbsent(): void {
    $this->cmd(
      'ahoy --version',
      '! [fatal]',
      'Calling local commands without local file does not throw an error'
    );
  }

  #[Group('p0')]
  public function testLocalConfigPresent(): void {
    File::copy('.ahoy.local.example.yml', '.ahoy.local.yml');
    $this->cmd(
      'ahoy local help',
      ['* Custom local commands', '! [fatal]'],
      'Calling local commands with local file path specified and file is present should succeed'
    );
  }

  #[Group('p0')]
  public function testLocalConfigPresentNonZeroExitCode(): void {
    File::copy('.ahoy.local.example.yml', '.ahoy.local.yml');

    $existing_content = File::read('.ahoy.local.yml');
    $this->assertNotEmpty($existing_content, 'Failed to read .ahoy.local.yml');

    File::dump('.ahoy.local.yml', $existing_content . <<<YAML

  mylocalcommand:
    cmd: |
      echo 'expected failure'
      exit 1
YAML
    );

    $this->cmdFail(
      'ahoy local mylocalcommand',
      ['* expected failure', '! [fatal]'],
      'Calling local commands with local file path specified and file is present and file return non-zero exit code should fail'
    );
  }

}
