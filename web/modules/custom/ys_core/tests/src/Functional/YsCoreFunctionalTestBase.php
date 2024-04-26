<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ys_core\Traits\ArrayTrait;
use Drupal\Tests\ys_core\Traits\AssertTrait;
use Drupal\Tests\ys_core\Traits\MockTrait;
use Drupal\Tests\ys_core\Traits\ReflectionTrait;

/**
 * Class YsCoreKernelTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\ys_core\Tests
 */
abstract class YsCoreFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
