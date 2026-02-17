<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_base\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\BrowserHtmlDebugTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class SwBaseFunctionalJavascriptTestBase.
 *
 * Base class for functional JavaScript tests.
 *
 * @package Drupal\sw_base\Tests
 */
abstract class SwBaseFunctionalJavascriptTestBase extends WebDriverTestBase {

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
