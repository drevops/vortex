<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

/**
 * Audit pages with Lighthouse.
 *
 * Ships a Lighthouse container, an Ahoy command and a CI step that audit the
 * performance, accessibility, best practices and SEO of the built site, write
 * HTML and JSON reports and compare the scores with optional budgets.
 */
class Lighthouse extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public static function processWeight(): int {
    return 95;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Performance and SEO audits with Lighthouse?';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): ?string {
    return <<<DOC
Lighthouse audits of performance, accessibility, best practices and SEO for the pages you list or the pages in the sitemap.

Runs locally with `ahoy test-lighthouse` and in CI after the tests, writing HTML and JSON reports for every page.

Scores can be compared with per-category budgets. The CI step is advisory until you make it blocking.
DOC;
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Adds a Lighthouse container, an Ahoy command and a CI step.';
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    return File::contains($this->destinationDir . '/.ahoy.yml', 'vortex-lighthouse');
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    if ($this->getResponseAsBool()) {
      return;
    }

    File::removeTokenAsync('LIGHTHOUSE');
  }

}
