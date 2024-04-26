<?php

declare(strict_types=1);

namespace Drupal\Tests\your_site_theme\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\ys_core\Traits\ArrayTrait;
use Drupal\Tests\ys_core\Traits\AssertTrait;
use Drupal\Tests\ys_core\Traits\MockTrait;
use Drupal\Tests\ys_core\Traits\ReflectionTrait;

/**
 * Class YourSiteThemeKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\your_site_theme\Tests
 */
abstract class YourSiteThemeKernelTestBase extends KernelTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
