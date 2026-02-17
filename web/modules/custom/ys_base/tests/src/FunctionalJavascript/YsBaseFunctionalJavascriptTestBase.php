<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_base\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ys_base\Traits\ArrayTrait;
use Drupal\Tests\ys_base\Traits\AssertTrait;
use Drupal\Tests\ys_base\Traits\BrowserHtmlDebugTrait;
use Drupal\Tests\ys_base\Traits\MockTrait;
use Drupal\Tests\ys_base\Traits\ReflectionTrait;

/**
 * Class YsBaseFunctionalJavascriptTestBase.
 *
 * Base class for functional JavaScript tests.
 *
 * @package Drupal\ys_base\Tests
 */
abstract class YsBaseFunctionalJavascriptTestBase extends WebDriverTestBase {

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
