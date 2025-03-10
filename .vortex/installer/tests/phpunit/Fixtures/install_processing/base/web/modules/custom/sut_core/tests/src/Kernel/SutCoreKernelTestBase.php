<?php

declare(strict_types=1);

namespace Drupal\Tests\sut_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sut_core\Traits\ArrayTrait;
use Drupal\Tests\sut_core\Traits\AssertTrait;
use Drupal\Tests\sut_core\Traits\MockTrait;
use Drupal\Tests\sut_core\Traits\ReflectionTrait;

/**
 * Class SutCoreKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\sut_core\Tests
 */
abstract class SutCoreKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
