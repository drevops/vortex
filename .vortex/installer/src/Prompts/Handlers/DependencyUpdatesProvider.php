<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;

class DependencyUpdatesProvider extends AbstractHandler {

  const NONE = 'none';

  const RENOVATEBOT_CI = 'renovatebot_ci';

  const RENOVATEBOT_APP = 'renovatebot_app';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Dependency updates provider';
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇ to select the dependency updates provider.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::RENOVATEBOT_APP => 'Renovate GitHub app',
      self::RENOVATEBOT_CI => 'Renovate self-hosted in CI',
      self::NONE => 'None',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::RENOVATEBOT_APP;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    if (!is_readable($this->dstDir . '/renovate.json')) {
      return self::NONE;
    }

    if (file_exists($this->dstDir . '/.github/workflows/update-dependencies.yml')) {
      return self::RENOVATEBOT_CI;
    }

    if (File::contains($this->dstDir . '/.circleci/config.yml', 'update-dependencies')) {
      return self::RENOVATEBOT_CI;
    }

    return self::RENOVATEBOT_APP;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    if ($v === self::RENOVATEBOT_CI) {
      File::removeTokenAsync('!DEPS_UPDATE_PROVIDER_CI');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_APP');
      $this->removeTemplateOnlyConfig();
    }
    elseif ($v === self::RENOVATEBOT_APP) {
      File::removeTokenAsync('!DEPS_UPDATE_PROVIDER_APP');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_CI');
      $this->removeTemplateOnlyConfig();
      File::remove($t . '/.github/workflows/update-dependencies.yml');
      File::remove($t . '/.circleci/update-dependencies.yml');
    }
    else {
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_APP');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER_CI');
      File::removeTokenAsync('DEPS_UPDATE_PROVIDER');
      File::remove($t . '/renovate.json');
      File::remove($t . '/.circleci/update-dependencies.yml');
    }
  }

  /**
   * Remove Renovate config entries that only apply to the template repository.
   *
   * The '.vortex' directory is not present in an installed project, so the
   * paths ignored under it and the custom manager tracking a file within it
   * can never match anything.
   */
  protected function removeTemplateOnlyConfig(): void {
    $file = $this->tmpDir . '/renovate.json';

    File::replaceContentInFile($file, '/\s*"ignorePaths":\s*\[[^\]]*\],?\n/s', "\n");
    File::replaceContentInFile($file, '/,\s*\{[^{}]*\.vortex[^{}]*\}(?=\s*\n\s*\])/s', '');
  }

}
