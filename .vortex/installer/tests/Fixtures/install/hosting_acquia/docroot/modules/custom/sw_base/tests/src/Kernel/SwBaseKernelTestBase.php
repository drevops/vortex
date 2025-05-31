<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_base\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class SwBaseKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\sw_base\Tests
 */
abstract class SwBaseKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
