<?php

declare(strict_types=1);

namespace Drupal\Tests\star_wars\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\sw_base\Traits\ArrayTrait;
use Drupal\Tests\sw_base\Traits\AssertTrait;
use Drupal\Tests\sw_base\Traits\MockTrait;
use Drupal\Tests\sw_base\Traits\ReflectionTrait;

/**
 * Class StarWarsUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\star_wars\Tests
 */
abstract class StarWarsUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
