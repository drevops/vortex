<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\ys_core\Traits\ArrayTrait;
use Drupal\Tests\ys_core\Traits\AssertTrait;
use Drupal\Tests\ys_core\Traits\MockTrait;
use Drupal\Tests\ys_core\Traits\ReflectionTrait;

/**
 * Class YsCoreKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\ys_core\Tests
 */
abstract class YsCoreKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
