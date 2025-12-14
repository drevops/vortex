<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for override execution functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\execute_override')]
#[Group('helpers')]
class HelpersOverrideTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  public function testExecuteOverrideNoCustomDir(): void {
    $this->envUnset('VORTEX_TOOLING_CUSTOM_DIR');

    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\execute_override('test-script');
  }

  public function testExecuteOverrideCustomDirNoFile(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    $this->envSet('VORTEX_TOOLING_CUSTOM_DIR', $custom_dir);

    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\execute_override('test-script');
  }

  public function testExecuteOverrideCustomDirNonExecutable(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    $this->envSet('VORTEX_TOOLING_CUSTOM_DIR', $custom_dir);

    $script_path = $custom_dir . '/test-script';
    file_put_contents($script_path, '#!/bin/bash\necho "override"');
    chmod($script_path, 0644);

    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\execute_override('test-script');
  }

  public function testExecuteOverrideSuccessWithExecutablePassingOverridePassing(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    $this->envSet('VORTEX_TOOLING_CUSTOM_DIR', $custom_dir);

    $script_path = $custom_dir . '/test-override-passing';
    file_put_contents($script_path, "#!/usr/bin/env php\n<?php\necho 'override executed';");
    chmod($script_path, 0755);

    $this->runScriptEarlyPass('tests/Fixtures/test-override-passing', 'override executed');
  }

  public function testExecuteOverrideSuccessWithExecutablePassingOverrideFailing(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    $this->envSet('VORTEX_TOOLING_CUSTOM_DIR', $custom_dir);

    $script_path = $custom_dir . '/test-override-passing';
    file_put_contents($script_path, "#!/usr/bin/env php\n<?php\necho 'override executed'; exit(1);");
    chmod($script_path, 0755);

    $this->runScriptError('tests/Fixtures/test-override-passing', 'override executed');
  }

  public function testExecuteOverrideFailureWithExecutableFailingOverridePassing(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    $this->envSet('VORTEX_TOOLING_CUSTOM_DIR', $custom_dir);

    $script_path = $custom_dir . '/test-override-failing';
    file_put_contents($script_path, "#!/usr/bin/env php\n<?php\necho 'override executed';");
    chmod($script_path, 0755);

    $this->runScriptEarlyPass('tests/Fixtures/test-override-failing', 'override executed');
  }

  public function testExecuteOverrideFailureWithExecutableFailingOverrideFailing(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    $this->envSet('VORTEX_TOOLING_CUSTOM_DIR', $custom_dir);

    $script_path = $custom_dir . '/test-override-failing';
    file_put_contents($script_path, "#!/usr/bin/env php\n<?php\necho 'override executed'; exit(1);");
    chmod($script_path, 0755);

    $this->runScriptError('tests/Fixtures/test-override-failing', 'override executed');
  }

  public function testExecuteOverrideWithFileInsteadOfDir(): void {
    $custom_dir = self::$tmp . '/custom';
    File::dump($custom_dir);
    $custom_dir = self::$tmp . '/custom';

    $this->envSet('VORTEX_TOOLING_CUSTOM_DIR', $custom_dir);

    $script_path = $custom_dir . '/test-override-passing';
    file_put_contents($script_path, "#!/usr/bin/env php\n<?php\necho 'override executed';");
    chmod($script_path, 0755);

    $this->runScriptError('tests/Fixtures/test-override-passing', sprintf('Custom directory specified in VORTEX_TOOLING_CUSTOM_DIR does not exist: %s', $custom_dir));
  }

}
