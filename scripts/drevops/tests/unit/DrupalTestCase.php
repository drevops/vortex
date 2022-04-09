<?php

namespace Drevops\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Class DrupalTestCase.
 *
 * Abstract base class for all Drupal-related unit tests.
 *
 * @package Drevops\Tests
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class DrupalTestCase extends TestCase {

  use DrevopsTestHelperTrait;

}
