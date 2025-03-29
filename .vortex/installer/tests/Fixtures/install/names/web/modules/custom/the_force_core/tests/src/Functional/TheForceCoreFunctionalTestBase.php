<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\the_force_core\Traits\ArrayTrait;
use Drupal\Tests\the_force_core\Traits\AssertTrait;
use Drupal\Tests\the_force_core\Traits\MockTrait;
use Drupal\Tests\the_force_core\Traits\ReflectionTrait;

/**
 * Class TheForceCoreKernelTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\the_force_core\Tests
 */
abstract class TheForceCoreFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
