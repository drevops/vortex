<?php

namespace Drupal\Tests\your_site_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\your_site_core\Traits\YourSiteCoreTestHelperTrait;

/**
 * Class YourSiteCoreKernelTestBase.
 *
 * Base class for kernel tests.
 */
abstract class YourSiteCoreKernelTestBase extends KernelTestBase {

  use YourSiteCoreTestHelperTrait;

}
