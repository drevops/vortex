<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sw_core\Traits\ArrayTrait;
use Drupal\Tests\sw_core\Traits\AssertTrait;
use Drupal\Tests\sw_core\Traits\MockTrait;
use Drupal\Tests\sw_core\Traits\ReflectionTrait;

/**
 * Class SwCoreKernelTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\sw_core\Tests
 */
abstract class SwCoreFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
