<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Tests for override execution functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\execute_override')]
class OverrideTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Load helpers to make functions available.
    require_once __DIR__ . '/../../src/helpers.php';
  }

  /**
   * Test execute_override() with no custom directory set.
   */
  public function testExecuteOverrideNoCustomDir(): void {
    putenv('VORTEX_TOOLING_CUSTOM_DIR');

    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\execute_override('test-script');
  }

  /**
   * Test execute_override() with custom directory but no override file.
   */
  public function testExecuteOverrideCustomDirNoFile(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    putenv('VORTEX_TOOLING_CUSTOM_DIR=' . $custom_dir);

    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\execute_override('test-script');
  }

  /**
   * Test execute_override() with custom directory and non-executable file.
   */
  public function testExecuteOverrideCustomDirNonExecutable(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    $script_path = $custom_dir . '/test-script';
    file_put_contents($script_path, '#!/bin/bash\necho "override"');
    chmod($script_path, 0644);
    putenv('VORTEX_TOOLING_CUSTOM_DIR=' . $custom_dir);

    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\execute_override('test-script');
  }

  /**
   * Test execute_override() with executable override file.
   */
  public function testExecuteOverrideWithExecutable(): void {
    $custom_dir = self::$tmp . '/custom';
    mkdir($custom_dir, 0777, TRUE);
    $script_path = $custom_dir . '/test-script';
    file_put_contents($script_path, "#!/usr/bin/env php\n<?php\necho 'override executed';\nexit(42);");
    chmod($script_path, 0755);
    putenv('VORTEX_TOOLING_CUSTOM_DIR=' . $custom_dir);

    $this->mockPassthru([
      'cmd' => sprintf('"%s"', $script_path),
      'output' => 'override executed',
      'result_code' => 42,
    ]);
    $this->mockQuit(42);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(42);

    try {
      ob_start();
      \DrevOps\VortexTooling\execute_override('test-script');
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertEquals('override executed', $output);
    }
  }

}
