<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_core\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\ys_core\Traits\ArrayTrait;
use Drupal\Tests\ys_core\Traits\AssertTrait;
use Drupal\Tests\ys_core\Traits\MockTrait;
use Drupal\Tests\ys_core\Traits\ReflectionTrait;

/**
 * Class YsCoreUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\ys_core\Tests
 */
abstract class YsCoreUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
