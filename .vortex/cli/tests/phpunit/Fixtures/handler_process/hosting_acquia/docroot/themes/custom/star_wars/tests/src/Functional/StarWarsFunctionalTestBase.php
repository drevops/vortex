<?php

declare(strict_types=1);

namespace Drupal\Tests\star_wars\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class StarWarsFunctionalTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\star_wars\Tests
 */
abstract class StarWarsFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
