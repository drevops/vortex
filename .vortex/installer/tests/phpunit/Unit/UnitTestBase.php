<?php

declare(strict_types=1);

namespace DrevOps\Installer\Tests\Unit;

use DrevOps\Installer\Tests\Traits\ClosureWrapperTrait;
use DrevOps\Installer\Tests\Traits\LocationsTrait;
use DrevOps\Installer\Tests\Traits\ReflectionTrait;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestStatus\Error;
use PHPUnit\Framework\TestStatus\Failure;

/**
 * Class UnitTestCase.
 *
 * UnitTestCase fixture class.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class UnitTestBase extends TestCase {

  use ReflectionTrait;
  use LocationsTrait;
  use ClosureWrapperTrait;

  protected function setUp(): void {
    self::locationsInit(getcwd() . '/../../');
  }

  protected function tearDown(): void {
    // Clean up the directories if the test passed.
    if (!$this->status() instanceof Failure && !$this->status() instanceof Error) {
      self::locationsTearDown();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function onNotSuccessfulTest(\Throwable $t): never {
    // Print the locations information and the exception message.
    fwrite(STDERR, PHP_EOL . 'See below:' . PHP_EOL . PHP_EOL . static::locationsInfo() . PHP_EOL . $t->getMessage() . PHP_EOL);

    parent::onNotSuccessfulTest($t);
  }

}
