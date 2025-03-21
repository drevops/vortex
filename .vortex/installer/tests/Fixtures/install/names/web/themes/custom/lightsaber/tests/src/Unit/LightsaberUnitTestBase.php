<?php

declare(strict_types=1);

namespace Drupal\Tests\lightsaber\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\the_force_core\Traits\ArrayTrait;
use Drupal\Tests\the_force_core\Traits\AssertTrait;
use Drupal\Tests\the_force_core\Traits\MockTrait;
use Drupal\Tests\the_force_core\Traits\ReflectionTrait;

/**
 * Class LightsaberUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\lightsaber\Tests
 */
abstract class LightsaberUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
