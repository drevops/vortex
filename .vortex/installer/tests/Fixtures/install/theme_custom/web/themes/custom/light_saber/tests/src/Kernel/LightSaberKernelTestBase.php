<?php

declare(strict_types=1);

namespace Drupal\Tests\light_saber\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sw_core\Traits\ArrayTrait;
use Drupal\Tests\sw_core\Traits\AssertTrait;
use Drupal\Tests\sw_core\Traits\MockTrait;
use Drupal\Tests\sw_core\Traits\ReflectionTrait;

/**
 * Class LightSaberKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\light_saber\Tests
 */
abstract class LightSaberKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
