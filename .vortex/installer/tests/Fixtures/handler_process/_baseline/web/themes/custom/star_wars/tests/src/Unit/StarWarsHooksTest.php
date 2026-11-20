<?php

declare(strict_types=1);

namespace Drupal\Tests\star_wars\Unit;

use Drupal\Core\Site\Settings;
use Drupal\star_wars\Hook\StarWarsHooks;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the theme hook implementations.
 *
 * @package Drupal\star_wars\Tests
 */
#[Group('StarWars')]
class StarWarsHooksTest extends StarWarsUnitTestBase {

  /**
   * Tests that preprocessHtml() hides the administration navigation.
   */
  #[DataProvider('dataProviderPreprocessHtml')]
  public function testPreprocessHtml(array $settings, array $expected): void {
    new Settings($settings);
    $variables = [
      'page_top' => [
        'navigation' => ['#markup' => 'navigation'],
        'top_bar' => ['#markup' => 'top_bar'],
        'toolbar' => ['#markup' => 'toolbar'],
      ],
    ];

    new StarWarsHooks()->preprocessHtml($variables);

    $this->assertSame($expected, array_keys($variables['page_top']));
  }

  /**
   * Data provider for testPreprocessHtml().
   */
  public static function dataProviderPreprocessHtml(): \Iterator {
    yield 'setting not set' => [[], ['navigation', 'top_bar', 'toolbar']];
    yield 'setting off' => [['hide_admin_navigation' => FALSE], ['navigation', 'top_bar', 'toolbar']];
    yield 'setting on' => [['hide_admin_navigation' => TRUE], ['toolbar']];
  }

}
