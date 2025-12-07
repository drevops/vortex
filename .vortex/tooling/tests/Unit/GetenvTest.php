<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for getenv helper functions.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('helpers')]
class GetenvTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';
  }

  public function testGetenvDefaultWithFirstVarSet(): void {
    $this->envSet('FIRST_VAR', 'first_value');

    $result = \DrevOps\VortexTooling\getenv_default('FIRST_VAR', 'SECOND_VAR', 'default');

    $this->assertEquals('first_value', $result);
  }

  public function testGetenvDefaultWithSecondVarSet(): void {
    $this->envSet('SECOND_VAR', 'second_value');

    $result = \DrevOps\VortexTooling\getenv_default('FIRST_VAR', 'SECOND_VAR', 'default');

    $this->assertEquals('second_value', $result);
  }

  public function testGetenvDefaultWithBothVarsSet(): void {
    $this->envSet('FIRST_VAR', 'first_value');
    $this->envSet('SECOND_VAR', 'second_value');

    $result = \DrevOps\VortexTooling\getenv_default('FIRST_VAR', 'SECOND_VAR', 'default');

    $this->assertEquals('first_value', $result);
  }

  public function testGetenvDefaultWithNoVarsSet(): void {
    $result = \DrevOps\VortexTooling\getenv_default('UNSET_VAR_1', 'UNSET_VAR_2', 'default_value');

    $this->assertEquals('default_value', $result);
  }

  public function testGetenvDefaultWithSingleVarSet(): void {
    $this->envSet('SINGLE_VAR', 'single_value');

    $result = \DrevOps\VortexTooling\getenv_default('SINGLE_VAR', 'default');

    $this->assertEquals('single_value', $result);
  }

  public function testGetenvDefaultWithSingleVarNotSet(): void {
    $result = \DrevOps\VortexTooling\getenv_default('UNSET_VAR', 'default');

    $this->assertEquals('default', $result);
  }

  public function testGetenvDefaultWithEmptyStringConsideredUnset(): void {
    $this->envSet('EMPTY_VAR', '');
    $this->envSet('NON_EMPTY_VAR', 'value');

    $result = \DrevOps\VortexTooling\getenv_default('EMPTY_VAR', 'NON_EMPTY_VAR', 'default');

    $this->assertEquals('value', $result);
  }

  public function testGetenvDefaultAllEmptyStringsUsesDefault(): void {
    $this->envSet('EMPTY_VAR_1', '');
    $this->envSet('EMPTY_VAR_2', '');

    $result = \DrevOps\VortexTooling\getenv_default('EMPTY_VAR_1', 'EMPTY_VAR_2', 'default');

    $this->assertEquals('default', $result);
  }

  public function testGetenvDefaultInvalidArgumentsThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('getenv_default() requires at least 2 arguments');

    \DrevOps\VortexTooling\getenv_default('SINGLE_ARG');
  }

  public function testGetenvRequiredWithFirstVarSet(): void {
    $this->envSet('FIRST_VAR', 'first_value');

    $result = \DrevOps\VortexTooling\getenv_required('FIRST_VAR', 'SECOND_VAR');

    $this->assertEquals('first_value', $result);
  }

  public function testGetenvRequiredWithSecondVarSet(): void {
    $this->envSet('SECOND_VAR', 'second_value');

    $result = \DrevOps\VortexTooling\getenv_required('FIRST_VAR', 'SECOND_VAR');

    $this->assertEquals('second_value', $result);
  }

  public function testGetenvRequiredWithBothVarsSet(): void {
    $this->envSet('FIRST_VAR', 'first_value');
    $this->envSet('SECOND_VAR', 'second_value');

    $result = \DrevOps\VortexTooling\getenv_required('FIRST_VAR', 'SECOND_VAR');

    $this->assertEquals('first_value', $result);
  }

  public function testGetenvRequiredWithSingleVarSet(): void {
    $this->envSet('REQUIRED_VAR', 'required_value');

    $result = \DrevOps\VortexTooling\getenv_required('REQUIRED_VAR');

    $this->assertEquals('required_value', $result);
  }

  public function testGetenvRequiredWithEmptyStringConsideredUnset(): void {
    $this->envSet('EMPTY_VAR', '');
    $this->envSet('NON_EMPTY_VAR', 'value');

    $result = \DrevOps\VortexTooling\getenv_required('EMPTY_VAR', 'NON_EMPTY_VAR');

    $this->assertEquals('value', $result);
  }

  public function testGetenvRequiredWithNoVarsSetFails(): void {
    $this->mockQuit(1);
    $this->expectException(QuitErrorException::class);

    $output = $this->captureOutput(function (): void {
      \DrevOps\VortexTooling\getenv_required('UNSET_VAR_1', 'UNSET_VAR_2');
    });

    $this->assertStringContainsString('Missing required value for UNSET_VAR_1, UNSET_VAR_2', $output);
  }

  public function testGetenvRequiredWithAllEmptyStringsFails(): void {
    $this->envSet('EMPTY_VAR_1', '');
    $this->envSet('EMPTY_VAR_2', '');

    $this->mockQuit(1);
    $this->expectException(QuitErrorException::class);

    $output = $this->captureOutput(function (): void {
      \DrevOps\VortexTooling\getenv_required('EMPTY_VAR_1', 'EMPTY_VAR_2');
    });

    $this->assertStringContainsString('Missing required value for EMPTY_VAR_1, EMPTY_VAR_2', $output);
  }

  public function testGetenvRequiredWithSingleVarNotSetFails(): void {
    $this->mockQuit(1);
    $this->expectException(QuitErrorException::class);

    $output = $this->captureOutput(function (): void {
      \DrevOps\VortexTooling\getenv_required('UNSET_VAR');
    });

    $this->assertStringContainsString('Missing required value for UNSET_VAR', $output);
  }

  public function testGetenvRequiredInvalidArgumentsThrowsException(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('getenv_required() requires at least 1 argument');

    \DrevOps\VortexTooling\getenv_required();
  }

  protected function captureOutput(callable $callback): string {
    ob_start();
    try {
      $callback();
    }
    catch (\Throwable $e) {
      $output = ob_get_clean();
      throw $e;
    }
    return ob_get_clean() ?: '';
  }

}
