<?php

declare(strict_types=1);

namespace Drupal\Tests\light_saber\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\BrowserHtmlDebugTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class LightSaberFunctionalJavascriptTestBase.
 *
 * Base class for functional JavaScript tests.
 *
 * @package Drupal\light_saber\Tests
 */
abstract class LightSaberFunctionalJavascriptTestBase extends WebDriverTestBase {

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
