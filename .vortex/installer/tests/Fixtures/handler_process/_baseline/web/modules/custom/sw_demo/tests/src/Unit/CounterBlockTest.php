<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_demo\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\sw_demo\Plugin\Block\CounterBlock;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the CounterBlock plugin.
 *
 * @package Drupal\sw_demo\Tests
 */
#[Group('SwDemo')]
class CounterBlockTest extends UnitTestCase {

  /**
   * Tests that build() returns the expected render array.
   */
  public function testBuild(): void {
    $block = new CounterBlock([], 'sw_demo_counter_block', ['provider' => 'sw_demo']);

    $build = $block->build();

    $this->assertEquals('sw_demo_counter_block', $build['#theme']);
    $this->assertEquals(0, $build['#counter_value']);
    $this->assertArrayHasKey('#attached', $build);
    $this->assertContains('sw_demo/counter', $build['#attached']['library']);
  }

  /**
   * Tests that getCacheMaxAge() returns zero for interactive block.
   */
  public function testGetCacheMaxAge(): void {
    $block = new CounterBlock([], 'sw_demo_counter_block', ['provider' => 'sw_demo']);

    $this->assertEquals(0, $block->getCacheMaxAge());
  }

}
