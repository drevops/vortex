<?php

namespace Drupal\Tests\your_site_theme\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\your_site_theme\Traits\YourSiteThemeTestHelperTrait;

/**
 * Class YourSiteThemeFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\your_site_theme\Tests
 */
abstract class YourSiteThemeFunctionalTestBase extends BrowserTestBase {

  use YourSiteThemeTestHelperTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
