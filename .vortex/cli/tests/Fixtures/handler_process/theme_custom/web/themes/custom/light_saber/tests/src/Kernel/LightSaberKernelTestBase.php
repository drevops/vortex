<?php

declare(strict_types=1);

namespace Drupal\Tests\light_saber\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

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
