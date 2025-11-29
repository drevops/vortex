<?php

declare(strict_types=1);

namespace Drupal\Tests\light_saber\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class LightSaberFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\light_saber\Tests
 */
abstract class LightSaberFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;

  use AssertTrait;

  use MockTrait;

  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
