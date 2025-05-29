<?php

declare(strict_types=1);

namespace Drupal\Tests\lightsaber\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\the_force_base\Traits\ArrayTrait;
use Drupal\Tests\the_force_base\Traits\AssertTrait;
use Drupal\Tests\the_force_base\Traits\MockTrait;
use Drupal\Tests\the_force_base\Traits\ReflectionTrait;

/**
 * Class LightsaberKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\lightsaber\Tests
 */
abstract class LightsaberKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
