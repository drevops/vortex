<?php

declare(strict_types=1);

namespace Drupal\Tests\sut_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sut_core\Traits\ArrayTrait;
use Drupal\Tests\sut_core\Traits\AssertTrait;
use Drupal\Tests\sut_core\Traits\MockTrait;
use Drupal\Tests\sut_core\Traits\ReflectionTrait;

/**
 * Class SutCoreKernelTestBase.
 *
 * Base class for functional tests.
 *
 * @package Drupal\sut_core\Tests
 */
abstract class SutCoreFunctionalTestBase extends BrowserTestBase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
