<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for validation functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\command_exists')]
#[Group('helpers')]
class CommandExistsTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  public function testCommandExistsAvailable(): void {
    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\command_exists('php');
  }

  public function testCommandExistsUnavailable(): void {
    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\command_exists('nonexistent_command_12345');
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
