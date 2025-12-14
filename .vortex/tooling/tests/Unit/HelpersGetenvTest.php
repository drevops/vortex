<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for getenv helper functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('helpers')]
class HelpersGetenvTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';
  }

  #[DataProvider('dataProviderGetenvDefault')]
  public function testGetenvDefault(array $env_vars, array $args, string|null|false $expected): void {
    foreach ($env_vars as $name => $value) {
      $this->envSet($name, $value);
    }

    $result = \DrevOps\VortexTooling\getenv_default(...$args);

    $this->assertEquals($expected, $result);
  }

  public static function dataProviderGetenvDefault(): array {
    return [
      'first var set' => [
        'env_vars' => ['FIRST_VAR' => 'first_value'],
        'args' => ['FIRST_VAR', 'SECOND_VAR', 'default'],
        'expected' => 'first_value',
      ],
      'second var set' => [
        'env_vars' => ['SECOND_VAR' => 'second_value'],
        'args' => ['FIRST_VAR', 'SECOND_VAR', 'default'],
        'expected' => 'second_value',
      ],
      'both vars set returns first' => [
        'env_vars' => ['FIRST_VAR' => 'first_value', 'SECOND_VAR' => 'second_value'],
        'args' => ['FIRST_VAR', 'SECOND_VAR', 'default'],
        'expected' => 'first_value',
      ],
      'no vars set returns default' => [
        'env_vars' => [],
        'args' => ['UNSET_VAR_1', 'UNSET_VAR_2', 'default_value'],
        'expected' => 'default_value',
      ],
      'single var set' => [
        'env_vars' => ['SINGLE_VAR' => 'single_value'],
        'args' => ['SINGLE_VAR', 'default'],
        'expected' => 'single_value',
      ],
      'single var not set returns default' => [
        'env_vars' => [],
        'args' => ['UNSET_VAR', 'default'],
        'expected' => 'default',
      ],
      'empty string skipped for non-empty' => [
        'env_vars' => ['EMPTY_VAR' => '', 'NON_EMPTY_VAR' => 'value'],
        'args' => ['EMPTY_VAR', 'NON_EMPTY_VAR', 'default'],
        'expected' => 'value',
      ],
      'all empty strings returns default' => [
        'env_vars' => ['EMPTY_VAR_1' => '', 'EMPTY_VAR_2' => ''],
        'args' => ['EMPTY_VAR_1', 'EMPTY_VAR_2', 'default'],
        'expected' => 'default',
      ],
      'var set to false string' => [
        'env_vars' => ['FALSE_VAR' => 'false'],
        'args' => ['FALSE_VAR', 'default'],
        'expected' => 'false',
      ],
      'var set to true string' => [
        'env_vars' => ['TRUE_VAR' => 'true'],
        'args' => ['TRUE_VAR', 'default'],
        'expected' => 'true',
      ],
      'no vars set returns null default' => [
        'env_vars' => [],
        'args' => ['UNSET_VAR', NULL],
        'expected' => NULL,
      ],
      'no vars set returns false default' => [
        'env_vars' => [],
        'args' => ['UNSET_VAR', FALSE],
        'expected' => FALSE,
      ],
      'var set overrides null default' => [
        'env_vars' => ['SET_VAR' => 'value'],
        'args' => ['SET_VAR', NULL],
        'expected' => 'value',
      ],
      'var set overrides false default' => [
        'env_vars' => ['SET_VAR' => 'value'],
        'args' => ['SET_VAR', FALSE],
        'expected' => 'value',
      ],
    ];
  }

  public function testGetenvDefaultInvalidArgumentsThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('getenv_default() requires at least 2 arguments');

    \DrevOps\VortexTooling\getenv_default('SINGLE_ARG');
  }

  #[DataProvider('dataProviderGetenvRequired')]
  public function testGetenvRequired(array $env_vars, array $args, string $expected): void {
    foreach ($env_vars as $name => $value) {
      $this->envSet($name, $value);
    }

    $result = \DrevOps\VortexTooling\getenv_required(...$args);

    $this->assertEquals($expected, $result);
  }

  public static function dataProviderGetenvRequired(): array {
    return [
      'first var set' => [
        'env_vars' => ['FIRST_VAR' => 'first_value'],
        'args' => ['FIRST_VAR', 'SECOND_VAR'],
        'expected' => 'first_value',
      ],
      'second var set' => [
        'env_vars' => ['SECOND_VAR' => 'second_value'],
        'args' => ['FIRST_VAR', 'SECOND_VAR'],
        'expected' => 'second_value',
      ],
      'both vars set returns first' => [
        'env_vars' => ['FIRST_VAR' => 'first_value', 'SECOND_VAR' => 'second_value'],
        'args' => ['FIRST_VAR', 'SECOND_VAR'],
        'expected' => 'first_value',
      ],
      'single var set' => [
        'env_vars' => ['REQUIRED_VAR' => 'required_value'],
        'args' => ['REQUIRED_VAR'],
        'expected' => 'required_value',
      ],
      'empty string skipped for non-empty' => [
        'env_vars' => ['EMPTY_VAR' => '', 'NON_EMPTY_VAR' => 'value'],
        'args' => ['EMPTY_VAR', 'NON_EMPTY_VAR'],
        'expected' => 'value',
      ],
      'var set to false string' => [
        'env_vars' => ['FALSE_VAR' => 'false'],
        'args' => ['FALSE_VAR'],
        'expected' => 'false',
      ],
      'var set to true string' => [
        'env_vars' => ['TRUE_VAR' => 'true'],
        'args' => ['TRUE_VAR'],
        'expected' => 'true',
      ],
    ];
  }

  #[DataProvider('dataProviderGetenvRequiredFails')]
  public function testGetenvRequiredFails(array $env_vars, array $args, string $expected_message): void {
    foreach ($env_vars as $name => $value) {
      $this->envSet($name, $value);
    }

    $this->mockQuit(1);
    $this->expectException(QuitErrorException::class);

    $output = $this->captureOutput(function () use ($args): void {
      \DrevOps\VortexTooling\getenv_required(...$args);
    });

    $this->assertStringContainsString($expected_message, $output);
  }

  public static function dataProviderGetenvRequiredFails(): array {
    return [
      'no vars set' => [
        'env_vars' => [],
        'args' => ['UNSET_VAR_1', 'UNSET_VAR_2'],
        'expected_message' => 'Missing required value for UNSET_VAR_1, UNSET_VAR_2',
      ],
      'all empty strings' => [
        'env_vars' => ['EMPTY_VAR_1' => '', 'EMPTY_VAR_2' => ''],
        'args' => ['EMPTY_VAR_1', 'EMPTY_VAR_2'],
        'expected_message' => 'Missing required value for EMPTY_VAR_1, EMPTY_VAR_2',
      ],
      'single var not set' => [
        'env_vars' => [],
        'args' => ['UNSET_VAR'],
        'expected_message' => 'Missing required value for UNSET_VAR',
      ],
    ];
  }

  public function testGetenvRequiredInvalidArgumentsThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('getenv_required() requires at least 1 argument');

    \DrevOps\VortexTooling\getenv_required();
  }

}
