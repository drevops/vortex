<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\VortexCli\Utils\File;

/**
 * Scan for committed secrets with Gitleaks.
 *
 * Ships a CI step that scans the codebase for hardcoded secrets such as
 * passwords, API keys and tokens. When disabled, the CI step and the
 * Gitleaks configuration file are removed.
 */
class Gitleaks extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Scan for committed secrets with Gitleaks?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Runs in CI to detect hardcoded passwords, API keys and tokens.';
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return $this->isInstalled() ? file_exists($this->dstDir . '/.gitleaks.toml') : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsBool();
    $t = $this->tmpDir;

    if (!$v) {
      File::remove($t . '/.gitleaks.toml');
      File::removeTokenAsync('CI_GITLEAKS');
    }
  }

}
