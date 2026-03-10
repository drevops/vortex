<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_demo\Kernel;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the CounterBlock plugin discovery and theme integration.
 *
 * @package Drupal\ys_demo\Tests
 */
#[Group('YsDemo')]
class CounterBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'ys_demo'];

  /**
   * Tests that the counter block plugin is discoverable.
   */
  public function testBlockPluginDiscovery(): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    $definition = $block_manager->getDefinition('ys_demo_counter_block', FALSE);

    $this->assertNotNull($definition, 'Counter block plugin is discoverable.');
    $this->assertEquals('ys_demo', $definition['provider']);
  }

  /**
   * Tests that the counter block theme hook is registered.
   */
  public function testThemeHookRegistration(): void {
    $this->container->get('theme.registry')->reset();
    $theme_registry = $this->container->get('theme.registry')->get();

    $this->assertArrayHasKey('ys_demo_counter_block', $theme_registry);
    $this->assertEquals('ys-demo-counter-block', $theme_registry['ys_demo_counter_block']['template']);
  }

  /**
   * Tests that the counter block can be instantiated via the plugin manager.
   */
  public function testBlockInstantiation(): void {
    /** @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = \Drupal::service('plugin.manager.block');
    /** @var \Drupal\Core\Block\BlockPluginInterface $block */
    $block = $block_manager->createInstance('ys_demo_counter_block');

    $build = $block->build();

    $this->assertEquals('ys_demo_counter_block', $build['#theme']);
    $this->assertEquals(0, $build['#counter_value']);
  }

}
