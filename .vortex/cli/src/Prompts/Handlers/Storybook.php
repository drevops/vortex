<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Prompts\Handlers;

use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\JsonManipulator;
use DrevOps\VortexCli\Utils\NpmLock;

/**
 * Enable the Storybook component library for the custom theme.
 *
 * Ships the Storybook Drupal module, the theme-side Storybook configuration
 * and a deploy step that compiles the stories and builds a static application
 * served by the site's own web server.
 */
class Storybook extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'Storybook component library?';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): ?string {
    return <<<DOC
Component library for the custom theme, rendered by Drupal itself.

Stories are authored in Twig next to each component, so the library shows the same markup the site renders.

A development server runs on demand with `ahoy storybook`. A static build is produced during provisioning and served at `/storybook` in local, CI and development environments.
DOC;
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Adds Storybook packages to the theme and a step to provisioning.';
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
  public function dependsOn(): ?array {
    return [Theme::id() => [Theme::CUSTOM]];
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    if (!$this->isInstalled()) {
      return NULL;
    }

    return File::exists($this->dstDir . '/.docker/config/nginx/storybook.conf');
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // A theme other than a custom one never reaches the prompt and leaves a
    // NULL response, and the files below are not covered by the theme's own
    // token, so the handler processes every response other than TRUE.
    if ($this->response === TRUE) {
      return;
    }

    $t = $this->tmpDir;
    $w = $this->webroot;

    File::remove([
      $t . '/.docker/config/nginx/storybook.conf',
      $t . '/' . $w . '/sites/default/includes/modules/settings.storybook.php',
      $t . '/' . $w . '/sites/default/includes/modules/services.storybook.yml',
      $t . '/' . $w . '/modules/custom/ys_base/src/Plugin/DeployStep/BuildStorybookDeployStep.php',
    ]);

    File::remove($this->themePaths('.storybook'));
    File::remove($this->themePaths('components/*/*.stories.twig'));

    $composer_json = JsonManipulator::fromFile($t . '/composer.json');
    if ($composer_json instanceof JsonManipulator) {
      $composer_json->removeSubNode('require', 'drupal/storybook');
      File::dump($t . '/composer.json', $composer_json->getContents());
    }

    foreach ($this->themePaths('package.json') as $manifest) {
      $package_json = JsonManipulator::fromFile($manifest);
      if (!$package_json instanceof JsonManipulator) {
        continue;
      }

      $package_json->removeSubNode('devDependencies', '@storybook/server-webpack5');
      $package_json->removeSubNode('devDependencies', 'storybook');
      $package_json->removeSubNode('scripts', 'storybook');
      $package_json->removeSubNode('scripts', 'storybook-build');
      $package_json->removeSubNode('overrides', 'webpack-dev-middleware');
      $package_json->removeMainKeyIfEmpty('overrides');
      File::dump($manifest, $package_json->getContents());

      // A lock file that still lists the removed dependencies makes 'npm ci'
      // abort on the first build.
      NpmLock::sync($manifest);
    }

    File::removeTokenAsync('STORYBOOK');
  }

  /**
   * Resolve a path pattern within every custom theme.
   *
   * @param string $pattern
   *   Pattern relative to a theme directory.
   *
   * @return array<int, string>
   *   Matching paths.
   */
  protected function themePaths(string $pattern): array {
    return glob($this->tmpDir . '/' . $this->webroot . '/themes/custom/*/' . $pattern) ?: [];
  }

}
