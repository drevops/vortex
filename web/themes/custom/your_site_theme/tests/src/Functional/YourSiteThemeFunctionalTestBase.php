<?php

declare(strict_types=1);

namespace Drupal\Tests\your_site_theme\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ys_core\Traits\ArrayTrait;
use Drupal\Tests\ys_core\Traits\AssertTrait;
use Drupal\Tests\ys_core\Traits\MockTrait;
use Drupal\Tests\ys_core\Traits\ReflectionTrait;

/**
 * Class YourSiteThemeFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\your_site_theme\Tests
 */
abstract class YourSiteThemeFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
