<?php

declare(strict_types=1);

namespace Drupal\Tests\persistent_deploy\Unit;

use Drupal\Core\Site\Settings;
use Drupal\persistent_deploy\PersistentDeployBase;
use Drupal\persistent_deploy\PersistentDeployInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the PersistentDeployBase helpers.
 *
 * @package Drupal\persistent_deploy\Tests
 */
#[Group('PersistentDeploy')]
class PersistentDeployBaseTest extends UnitTestCase {

  /**
   * Tests weight, phase, label, and the default open gate.
   */
  public function testWeightPhaseLabelAndDefaultGate(): void {
    $step = $this->createStep([
      'weight' => 5,
      'label' => 'My step',
      'phase' => PersistentDeployInterface::PHASE_PRE,
    ]);

    $this->assertSame(5, $step->getWeight());
    $this->assertSame(PersistentDeployInterface::PHASE_PRE, $step->getPhase());
    $this->assertSame('My step', $step->label());
    $this->assertNull($step->gate(), 'The default gate is open.');
  }

  /**
   * Tests that weight, phase, and label fall back to sensible defaults.
   */
  public function testDefaultsFallBack(): void {
    $step = $this->createStep([]);

    $this->assertSame(0, $step->getWeight());
    $this->assertSame(PersistentDeployInterface::PHASE_POST, $step->getPhase());
    $this->assertSame('test_step', $step->label());
  }

  /**
   * Tests environment detection.
   */
  #[DataProvider('dataProviderEnvironment')]
  public function testEnvironment(string $value, bool $expected_production): void {
    new Settings(['environment' => $value]);
    $step = $this->createStep([]);

    $this->assertSame($value, $this->invoke($step, 'environment'));
    $this->assertSame($expected_production, $this->invoke($step, 'isProduction'));
  }

  /**
   * Data provider for testEnvironment().
   */
  public static function dataProviderEnvironment(): \Iterator {
    yield 'production' => ['prod', TRUE];
    yield 'local' => ['local', FALSE];
    yield 'ci' => ['ci', FALSE];
    yield 'stage' => ['stage', FALSE];
    yield 'dev' => ['dev', FALSE];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    // Reset the Settings singleton to an empty instance so environment state
    // does not leak into other tests that share the same process.
    new Settings([]);

    parent::tearDown();
  }

  /**
   * Creates a concrete deploy step with the given plugin definition.
   *
   * @param array $definition
   *   The plugin definition (may contain 'weight', 'phase' and 'label').
   *
   * @return \Drupal\persistent_deploy\PersistentDeployBase
   *   A concrete deploy step instance.
   */
  protected function createStep(array $definition): PersistentDeployBase {
    return new class([], 'test_step', $definition) extends PersistentDeployBase {

      /**
       * {@inheritdoc}
       */
      public function run(): void {
      }

    };
  }

  /**
   * Invokes a protected method on the given object.
   *
   * @param object $object
   *   The object to invoke the method on.
   * @param string $method
   *   The protected method name.
   *
   * @return mixed
   *   The method return value.
   */
  protected function invoke(object $object, string $method): mixed {
    $reflection = new \ReflectionMethod($object, $method);

    return $reflection->invoke($object);
  }

}
