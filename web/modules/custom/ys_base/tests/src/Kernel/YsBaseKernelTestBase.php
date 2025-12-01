<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_base\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\ys_base\Traits\ArrayTrait;
use Drupal\Tests\ys_base\Traits\AssertTrait;
use Drupal\Tests\ys_base\Traits\MockTrait;
use Drupal\Tests\ys_base\Traits\ReflectionTrait;

/**
 * Class YsBaseKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\ys_base\Tests
 */
abstract class YsBaseKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
