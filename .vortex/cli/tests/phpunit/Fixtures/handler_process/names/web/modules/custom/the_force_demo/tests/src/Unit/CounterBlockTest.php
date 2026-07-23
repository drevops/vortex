<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_demo\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\the_force_demo\Plugin\Block\CounterBlock;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the CounterBlock plugin.
 *
 * @package Drupal\the_force_demo\Tests
 */
#[Group('TheForceDemo')]
class CounterBlockTest extends UnitTestCase {

  /**
   * Tests that build() returns the expected render array.
   */
  public function testBuild(): void {
    $block = new CounterBlock([], 'the_force_demo_counter_block', ['provider' => 'the_force_demo']);

    $build = $block->build();

    $this->assertEquals('the_force_demo_counter_block', $build['#theme']);
    $this->assertEquals(0, $build['#counter_value']);
    $this->assertArrayHasKey('#attached', $build);
    $this->assertContains('the_force_demo/counter', $build['#attached']['library']);
  }

  /**
   * Tests that getCacheMaxAge() returns zero for interactive block.
   */
  public function testGetCacheMaxAge(): void {
    $block = new CounterBlock([], 'the_force_demo_counter_block', ['provider' => 'the_force_demo']);

    $this->assertEquals(0, $block->getCacheMaxAge());
  }

}
