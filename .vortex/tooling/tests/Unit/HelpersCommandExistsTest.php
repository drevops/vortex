<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for command existence functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\command_exists')]
#[CoversFunction('DrevOps\VortexTooling\command_must_exist')]
#[Group('helpers')]
class HelpersCommandExistsTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderCommandExists')]
  public function testCommandExists(string $command, bool $expected): void {
    $result = \DrevOps\VortexTooling\command_exists($command);

    $this->assertSame($expected, $result);
  }

  public static function dataProviderCommandExists(): array {
    return [
      'existing command php' => [
        'command' => 'php',
        'expected' => TRUE,
      ],
      'existing command ls' => [
        'command' => 'ls',
        'expected' => TRUE,
      ],
      'non-existing command' => [
        'command' => 'nonexistent_command_12345',
        'expected' => FALSE,
      ],
      'non-existing command with special chars' => [
        'command' => 'fake_cmd_xyz',
        'expected' => FALSE,
      ],
    ];
  }

  public function testCommandMustExistAvailable(): void {
    $this->expectNotToPerformAssertions();

    \DrevOps\VortexTooling\command_must_exist('php');
  }

  public function testCommandMustExistUnavailable(): void {
    $this->mockQuit(1);

    ob_start();
    try {
      \DrevOps\VortexTooling\command_must_exist('nonexistent_command_12345');
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
