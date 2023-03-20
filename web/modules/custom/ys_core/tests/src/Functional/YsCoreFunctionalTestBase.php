<?php

namespace Drupal\Tests\ys_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ys_core\Traits\YsCoreTestHelperTrait;

/**
 * Class YsCoreKernelTestBase.
 */
abstract class YsCoreFunctionalTestBase extends BrowserTestBase {

  use YsCoreTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
