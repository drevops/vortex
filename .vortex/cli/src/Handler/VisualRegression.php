<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\VortexCli\Utils\File;

/**
 * Enable Diffy visual regression testing.
 *
 * Ships a dedicated GitHub Actions workflow that runs Diffy comparisons
 * after each deployment and reports back on the PR. The workflow can
 * also be triggered manually for ad-hoc comparisons against an
 * arbitrary URL.
 */
class VisualRegression extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Visual regression testing with Diffy?';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): string {
    return <<<DOC
Diffy-powered visual regression workflow to compare deployed environments.

Automatically triggers comparisons for dependency-update pull requests matching the configured branch pattern (`deps/*` by default).

Runs comparisons for pull requests tagged with the `VR` label and posts a sticky comment.

Also supports manual runs against any URL.
DOC;
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Requires a Diffy account.';
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
    return $this->isInstalled() ? file_exists($this->dstDir . '/.github/workflows/test-vr.yml') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsBool();
    $t = $this->tmpDir;

    if (!$v) {
      File::remove($t . '/.github/workflows/test-vr.yml');
    }

    // The 'diffy' channel in the notify router is gated by
    // VORTEX_NOTIFY_CHANNELS so it stays dormant unless explicitly
    // enabled. The token markers around it in notify are intentionally
    // left in place even when VR is off - the channel is shipped as
    // part of the vortex-tooling Composer package, not the consumer
    // template, and the marker comments document the block boundary.
  }

}
