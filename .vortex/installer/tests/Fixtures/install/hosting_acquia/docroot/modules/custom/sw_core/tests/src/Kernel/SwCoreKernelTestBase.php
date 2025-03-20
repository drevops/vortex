<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sw_core\Traits\ArrayTrait;
use Drupal\Tests\sw_core\Traits\AssertTrait;
use Drupal\Tests\sw_core\Traits\MockTrait;
use Drupal\Tests\sw_core\Traits\ReflectionTrait;

/**
 * Class SwCoreKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\sw_core\Tests
 */
abstract class SwCoreKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
