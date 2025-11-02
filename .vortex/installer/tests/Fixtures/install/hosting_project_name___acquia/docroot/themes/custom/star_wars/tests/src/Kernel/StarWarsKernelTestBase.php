<?php

declare(strict_types=1);

namespace Drupal\Tests\star_wars\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class StarWarsKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\star_wars\Tests
 */
abstract class StarWarsKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
