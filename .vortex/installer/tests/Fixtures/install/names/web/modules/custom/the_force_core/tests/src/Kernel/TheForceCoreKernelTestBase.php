<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\the_force_core\Traits\ArrayTrait;
use Drupal\Tests\the_force_core\Traits\AssertTrait;
use Drupal\Tests\the_force_core\Traits\MockTrait;
use Drupal\Tests\the_force_core\Traits\ReflectionTrait;

/**
 * Class TheForceCoreKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\the_force_core\Tests
 */
abstract class TheForceCoreKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
