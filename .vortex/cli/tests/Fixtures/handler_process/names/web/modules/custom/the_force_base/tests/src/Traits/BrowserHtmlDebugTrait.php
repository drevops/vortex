<?php

declare(strict_types=1);

namespace Drupal\Tests\the_force_base\Traits;

/**
 * Trait BrowserHtmlDebugTrait.
 *
 * Provides screenshot capture for browser tests.
 *
 * @codeCoverageIgnore
 */
trait BrowserHtmlDebugTrait {

  /**
   * Take a screenshot and save it to the browser output directory.
   *
   * Uses the BROWSERTEST_OUTPUT_DIRECTORY environment variable if set,
   * falling back to the Drupal core default location.
   *
   * @param string $suffix
   *   Optional suffix to append to the filename. Defaults to a timestamp.
   *
   * @see https://www.drupal.org/project/drupal/issues/2992069
   */
  protected function takeScreenshot(string $suffix = ''): void {
    $directory = getenv('BROWSERTEST_OUTPUT_DIRECTORY') ?: DRUPAL_ROOT . '/sites/simpletest/browser_output';

    if (!is_dir($directory)) {
      mkdir($directory, 0775, TRUE);
    }

    $class = str_replace('\\', '_', static::class);
    $suffix = $suffix !== '' ? $suffix : date('Ymd_His');

    $this->createScreenshot($directory . '/' . $class . '-' . $suffix . '.png');
  }

}
