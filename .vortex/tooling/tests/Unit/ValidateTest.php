<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Tests for validation functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\validate_variable')]
#[CoversFunction('DrevOps\VortexTooling\validate_command')]
class ValidateTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Load helpers to make functions available.
    require_once __DIR__ . '/../../src/helpers.php';
  }

  /**
   * Test validate_variable() with set variable.
   */
  public function testValidateVariableSet(): void {
    putenv('TEST_VALIDATE_VAR=value');

    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\validate_variable('TEST_VALIDATE_VAR');
  }

  /**
   * Test validate_variable() with unset variable.
   */
  public function testValidateVariableUnset(): void {
    putenv('TEST_VALIDATE_VAR');

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);

    ob_start();
    try {
      \DrevOps\VortexTooling\validate_variable('TEST_VALIDATE_VAR');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
      throw $e;
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString('Missing required value for variable TEST_VALIDATE_VAR', $output);
    }
  }

  /**
   * Test validate_variable() with empty variable.
   */
  public function testValidateVariableEmpty(): void {
    putenv('TEST_VALIDATE_VAR=');

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);

    ob_start();
    try {
      \DrevOps\VortexTooling\validate_variable('TEST_VALIDATE_VAR');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
      throw $e;
    }
    finally {
      $output = ob_get_clean();
    }
  }

  /**
   * Test validate_command() with available command.
   */
  public function testValidateCommandAvailable(): void {
    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\validate_command('php');
  }

  /**
   * Test validate_command() with unavailable command.
   */
  public function testValidateCommandUnavailable(): void {
    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\validate_command('nonexistent_command_12345');
      $this->fail('Expected QuitErrorException to be thrown');
    }
    catch (QuitErrorException $e) {
      $this->assertEquals(1, $e->getCode());
    }
    finally {
      $output = ob_get_clean();
      $this->assertIsString($output);
      $this->assertStringContainsString("Command 'nonexistent_command_12345' is not available", $output);
    }
  }

}
