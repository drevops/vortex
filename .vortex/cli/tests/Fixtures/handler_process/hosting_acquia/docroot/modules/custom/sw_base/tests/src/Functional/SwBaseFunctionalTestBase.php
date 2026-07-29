<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_base\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class SwBaseFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\sw_base\Tests
 */
abstract class SwBaseFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
