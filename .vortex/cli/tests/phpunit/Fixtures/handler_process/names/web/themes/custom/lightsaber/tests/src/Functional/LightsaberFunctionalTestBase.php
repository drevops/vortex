<?php

declare(strict_types=1);

namespace Drupal\Tests\lightsaber\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\the_force_base\Traits\ArrayTrait;
use Drupal\Tests\the_force_base\Traits\AssertTrait;
use Drupal\Tests\the_force_base\Traits\MockTrait;
use Drupal\Tests\the_force_base\Traits\ReflectionTrait;

/**
 * Class LightsaberFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\lightsaber\Tests
 */
abstract class LightsaberFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
