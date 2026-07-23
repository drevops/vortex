<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_base\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\the_force_base\Traits\ArrayTrait;
use Drupal\Tests\the_force_base\Traits\AssertTrait;
use Drupal\Tests\the_force_base\Traits\MockTrait;
use Drupal\Tests\the_force_base\Traits\ReflectionTrait;

/**
 * Class TheForceBaseKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\the_force_base\Tests
 */
abstract class TheForceBaseKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
