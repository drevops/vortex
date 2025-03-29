<?php

declare(strict_types=1);

namespace Drupal\Tests\light_saber\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\sw_core\Traits\ArrayTrait;
use Drupal\Tests\sw_core\Traits\AssertTrait;
use Drupal\Tests\sw_core\Traits\MockTrait;
use Drupal\Tests\sw_core\Traits\ReflectionTrait;

/**
 * Class LightSaberUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\light_saber\Tests
 */
abstract class LightSaberUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
