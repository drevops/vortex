<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_base\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class SwBaseUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\sw_base\Tests
 */
abstract class SwBaseUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
