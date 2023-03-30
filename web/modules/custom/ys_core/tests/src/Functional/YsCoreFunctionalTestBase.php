<?php

namespace Drupal\Tests\ys_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ys_core\Traits\YsCoreTestHelperTrait;

/**
 * Class YsCoreKernelTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\ys_core\Tests
 */
abstract class YsCoreFunctionalTestBase extends BrowserTestBase {

  use YsCoreTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
