<?php

declare(strict_types=1);

namespace Drupal\Tests\your_site_theme\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\ys_base\Traits\ArrayTrait;
use Drupal\Tests\ys_base\Traits\AssertTrait;
use Drupal\Tests\ys_base\Traits\MockTrait;
use Drupal\Tests\ys_base\Traits\ReflectionTrait;

/**
 * Class YourSiteThemeUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\your_site_theme\Tests
 */
abstract class YourSiteThemeUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
