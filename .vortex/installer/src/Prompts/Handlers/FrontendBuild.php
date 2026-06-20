<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Env;

/**
 * Toggle building of front-end (theme) assets inside the container image.
 *
 * Only relevant for a custom theme. When disabled, the installer sets
 * VORTEX_FRONTEND_BUILD_SKIP=1 in .env so the container image build skips the
 * theme build and assets are built on the host or as part of deployment.
 */
class FrontendBuild extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Build front-end assets in the container?';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Disable to build theme assets on the host or as part of deployment.';
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
  public function dependsOn(): ?array {
    return [Theme::id() => [Theme::CUSTOM]];
  }

  /**
   * {@inheritdoc}
   */
  public function shouldRun(array $responses): bool {
    return isset($responses[Theme::id()]) && $responses[Theme::id()] === Theme::CUSTOM;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    // Build in the container unless the project explicitly opted out.
    return Env::getFromDotenv('VORTEX_FRONTEND_BUILD_SKIP', $this->dstDir) !== '1';
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // Only persist an explicit boolean answer; a non-boolean response means
    // the prompt was not shown.
    if (is_bool($this->response)) {
      Env::writeValueDotenv('VORTEX_FRONTEND_BUILD_SKIP', $this->response ? '0' : '1', $this->tmpDir . '/.env');
    }
  }

}
