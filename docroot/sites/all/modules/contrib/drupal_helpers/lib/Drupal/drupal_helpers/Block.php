<?php

namespace Drupal\drupal_helpers;

/**
 * Class Block.
 *
 * @package Drupal\drupal_helpers
 */
class Block {

  /**
   * Renders a block.
   *
   * @param string $block_delta
   *   Block delta.
   * @param string $block_module
   *   Block module name.
   * @param bool $renderable_array
   *   If TRUE, a renderable array is returned, FALSE -  a rendered string.
   */
  public static function render($block_delta, $block_module, $renderable_array = FALSE) {
    $block = block_load($block_module, $block_delta);
    $render = _block_get_renderable_array(_block_render_blocks([$block]));

    if ($renderable_array) {
      return $render;
    }
    else {
      return render($render);
    }
  }

  /**
   * Place a block in a region using core block module.
   *
   * @param string $block_delta
   *   Block delta.
   * @param string $block_module
   *   Block module machine name.
   * @param string $region
   *   Region machine name.
   * @param string $theme
   *   Theme machine name.
   * @param int $weight
   *   Block weight.
   */
  public static function place($block_delta, $block_module, $region, $theme, $weight = 0) {
    _block_rehash($theme);
    db_update('block')
      ->fields([
        'status' => 1,
        'weight' => $weight,
        'region' => $region,
      ])
      ->condition('module', $block_module)
      ->condition('delta', $block_delta)
      ->condition('theme', $theme)
      ->execute();

    General::messageSet(format_string('Block "@block_module-@block_delta" successfully added to the "@region" region in "@theme" theme.', [
      '@block_delta' => $block_delta,
      '@block_module' => $block_module,
      '@region' => $region,
      '@theme' => $theme,
    ]));
  }

  /**
   * Remove a block from a region using core block module.
   *
   * @param string $block_delta
   *   Block delta.
   * @param string $block_module
   *   Block module machine name.
   * @param string $theme
   *   Theme machine name.
   */
  public static function remove($block_delta, $block_module, $theme) {
    _block_rehash($theme);
    db_update('block')
      ->fields([
        'status' => 0,
      ])
      ->condition('module', $block_module)
      ->condition('delta', $block_delta)
      ->condition('theme', $theme)
      ->execute();

    General::messageSet(format_string('Block "@block_module-@block_delta" successfully removed from "@theme" theme.', [
      '@block_delta' => $block_delta,
      '@block_module' => $block_module,
      '@theme' => $theme,
    ]));
  }

  /**
   * Remove all blocks from all regions for specified theme.
   *
   * @param string $theme
   *   Theme machine name.
   */
  public static function removeAll($theme) {
    _block_rehash($theme);
    db_update('block')
      ->fields([
        'status' => 0,
      ])
      ->condition('theme', $theme)
      ->execute();

    General::messageSet(format_string('All blocks successfully removed from all regions in "@theme" theme.', [
      '@theme' => $theme,
    ]));
  }

  /**
   * Set the block visibility in the core block admin page.
   *
   * @param string $block_delta
   *   Block delta.
   * @param string $block_module
   *   Block module machine name.
   * @param string $theme
   *   Theme machine name.
   * @param string $pages
   *   List of paths to evaluate against the visibility rule. Multiple values
   *   should be delimited with new line character.
   * @param int $visibility
   *   One of the pre-defined block visibility constants:
   *   BLOCK_VISIBILITY_LISTED, BLOCK_VISIBILITY_NOTLISTED,
   *   BLOCK_VISIBILITY_PHP.
   */
  public static function visibility($block_delta, $block_module, $theme, $pages, $visibility = BLOCK_VISIBILITY_LISTED) {
    _block_rehash($theme);
    db_update('block')
      ->fields([
        'visibility' => $visibility,
        'pages' => $pages,
      ])
      ->condition('module', $block_module)
      ->condition('delta', $block_delta)
      ->condition('theme', $theme)
      ->execute();

    General::messageSet(format_string('Block "@block_module-@block_delta" successfully configured with visibility rules.', [
      '@block_delta' => $block_delta,
      '@block_module' => $block_module,
    ]));
  }

  /**
   * Set the block title in the core block admin page.
   *
   * @param string $block_delta
   *   Block delta.
   * @param string $block_module
   *   Block module machine name.
   * @param string $theme
   *   Theme machine name.
   * @param string $title
   *   The value to set the block title.
   *   - Specify "<none>" to remove the title entirely.
   *   - Specify empty string to use the default block title.
   */
  public static function title($block_delta, $block_module, $theme, $title) {
    _block_rehash($theme);
    db_update('block')
      ->fields([
        'title' => $title,
      ])
      ->condition('module', $block_module)
      ->condition('delta', $block_delta)
      ->condition('theme', $theme)
      ->execute();

    General::messageSet(format_string('Block "@block_module-@block_delta" successfully configured with title "@title".', [
      '@block_delta' => $block_delta,
      '@block_module' => $block_module,
      '@title' => $title,
    ]));
  }

}
