<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class CodeProvider extends AbstractHandler {

  const GITHUB = 'github';

  const OTHER = 'other';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Repository provider';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): string {
    return 'Vortex offers full automation with GitHub, while support for other providers is limited.';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select your code repository provider.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::GITHUB => 'GitHub',
      self::OTHER => 'Other',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::GITHUB;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (file_exists($this->dstDir . '/.github')) {
      return self::GITHUB;
    }

    return $this->isInstalled() && file_exists($this->dstDir . '/.git') ? self::OTHER : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    if ($v === self::GITHUB) {
      if (file_exists($t . '/.github/PULL_REQUEST_TEMPLATE.md')) {
        File::remove($t . '/.github/PULL_REQUEST_TEMPLATE.md');
      }

      if (file_exists($t . '/.github/PULL_REQUEST_TEMPLATE.dist.md')) {
        rename($t . '/.github/PULL_REQUEST_TEMPLATE.dist.md', $t . '/.github/PULL_REQUEST_TEMPLATE.md');
      }
    }
    else {
      File::remove($t . '/.github');
    }
  }

}
