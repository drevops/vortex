<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_base\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ys_base\Traits\ArrayTrait;
use Drupal\Tests\ys_base\Traits\AssertTrait;
use Drupal\Tests\ys_base\Traits\MockTrait;
use Drupal\Tests\ys_base\Traits\ReflectionTrait;

/**
 * Class YsBaseFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\ys_base\Tests
 */
abstract class YsBaseFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
