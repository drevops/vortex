<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_base\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\the_force_base\Traits\ArrayTrait;
use Drupal\Tests\the_force_base\Traits\AssertTrait;
use Drupal\Tests\the_force_base\Traits\MockTrait;
use Drupal\Tests\the_force_base\Traits\ReflectionTrait;

/**
 * Class TheForceBaseFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\the_force_base\Tests
 */
abstract class TheForceBaseFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
