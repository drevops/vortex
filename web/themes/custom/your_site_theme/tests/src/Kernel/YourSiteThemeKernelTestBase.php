<?php

namespace Drupal\Tests\your_site_theme\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\your_site_theme\Traits\YourSiteThemeTestHelperTrait;

/**
 * Class YourSiteThemeKernelTestBase.
 *
 * Base class for kernel tests.
 *
 * @package Drupal\your_site_theme\Tests
 */
abstract class YourSiteThemeKernelTestBase extends KernelTestBase {

  use YourSiteThemeTestHelperTrait;

}
