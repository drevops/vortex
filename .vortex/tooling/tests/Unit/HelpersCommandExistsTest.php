<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for command path functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\command_path')]
#[CoversFunction('DrevOps\VortexTooling\command_must_exist')]
#[Group('helpers')]
class HelpersCommandExistsTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderCommandPath')]
  public function testCommandPath(string $command, bool $expect_found): void {
    $result = \DrevOps\VortexTooling\command_path($command);

    if ($expect_found) {
      $this->assertIsString($result);
      $this->assertNotEmpty($result);
      $this->assertStringContainsString($command, $result);
    }
    else {
      $this->assertFalse($result);
    }
  }

  public static function dataProviderCommandPath(): array {
    return [
      'existing command php' => [
        'command' => 'php',
        'expect_found' => TRUE,
      ],
      'existing command ls' => [
        'command' => 'ls',
        'expect_found' => TRUE,
      ],
      'non-existing command' => [
        'command' => 'nonexistent_command_12345',
        'expect_found' => FALSE,
      ],
      'non-existing command with special chars' => [
        'command' => 'fake_cmd_xyz',
        'expect_found' => FALSE,
      ],
      'command with shell injection' => [
        'command' => 'php; echo pwned',
        'expect_found' => FALSE,
      ],
      'command with backticks' => [
        'command' => '`whoami`',
        'expect_found' => FALSE,
      ],
      'command with subshell' => [
        'command' => '$(whoami)',
        'expect_found' => FALSE,
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
