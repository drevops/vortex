<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_base\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\the_force_base\Traits\ArrayTrait;
use Drupal\Tests\the_force_base\Traits\AssertTrait;
use Drupal\Tests\the_force_base\Traits\BrowserHtmlDebugTrait;
use Drupal\Tests\the_force_base\Traits\MockTrait;
use Drupal\Tests\the_force_base\Traits\ReflectionTrait;

/**
 * Class TheForceBaseFunctionalJavascriptTestBase.
 *
 * Base class for functional JavaScript tests.
 *
 * @package Drupal\the_force_base\Tests
 */
abstract class TheForceBaseFunctionalJavascriptTestBase extends WebDriverTestBase {

  use ArrayTrait;
  use AssertTrait;
  use BrowserHtmlDebugTrait;
  use MockTrait;
  use ReflectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
