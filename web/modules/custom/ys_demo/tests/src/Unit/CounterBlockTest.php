<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_demo\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ys_demo\Plugin\Block\CounterBlock;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the CounterBlock plugin.
 *
 * @package Drupal\ys_demo\Tests
 */
#[Group('YsDemo')]
class CounterBlockTest extends UnitTestCase {

  /**
   * Tests that build() returns the expected render array.
   */
  public function testBuild(): void {
    $block = new CounterBlock([], 'ys_demo_counter_block', ['provider' => 'ys_demo']);

    $build = $block->build();

    $this->assertEquals('ys_demo_counter_block', $build['#theme']);
    $this->assertEquals(0, $build['#counter_value']);
    $this->assertArrayHasKey('#attached', $build);
    $this->assertContains('ys_demo/counter', $build['#attached']['library']);
  }

  /**
   * Tests that getCacheMaxAge() returns zero for interactive block.
   */
  public function testGetCacheMaxAge(): void {
    $block = new CounterBlock([], 'ys_demo_counter_block', ['provider' => 'ys_demo']);

    $this->assertEquals(0, $block->getCacheMaxAge());
  }

}
