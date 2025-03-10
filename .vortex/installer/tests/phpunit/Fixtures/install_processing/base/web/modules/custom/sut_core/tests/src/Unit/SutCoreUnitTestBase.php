<?php

declare(strict_types=1);

namespace Drupal\Tests\sut_core\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\sut_core\Traits\ArrayTrait;
use Drupal\Tests\sut_core\Traits\AssertTrait;
use Drupal\Tests\sut_core\Traits\MockTrait;
use Drupal\Tests\sut_core\Traits\ReflectionTrait;

/**
 * Class SutCoreUnitTestBase.
 *
 * Base class for all unit test cases.
 *
 * @package Drupal\sut_core\Tests
 */
abstract class SutCoreUnitTestBase extends UnitTestCase {

  use ArrayTrait;
  use AssertTrait;
  use MockTrait;
  use ReflectionTrait;

}
