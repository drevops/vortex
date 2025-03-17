<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_core\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\sw_core\Traits\ArrayTrait;
use Drupal\Tests\sw_core\Traits\AssertTrait;
use Drupal\Tests\sw_core\Traits\MockTrait;
use Drupal\Tests\sw_core\Traits\ReflectionTrait;

/**
 * Class SwCoreUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\sw_core\Tests
 */
abstract class SwCoreUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
