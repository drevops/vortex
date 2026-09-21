<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\JsonManipulator;
use DrevOps\VortexInstaller\Utils\NpmLock;

/**
 * Enable the Storybook component library for the custom theme.
 *
 * Ships the Storybook Drupal module, the theme-side Storybook configuration
 * and a provisioning step that compiles the stories and publishes a static
 * application served by the site's own web server.
 */
class Storybook extends AbstractHandler {

  /**
   * {@inheritdoc}
   */
  public static function processWeight(): int {
    return 345;
  }

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

A development server runs on demand with `ahoy storybook`. A static build is published during provisioning and served at `/storybook` in local, CI and development environments.
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

    return File::exists($this->destinationDir . '/scripts/provision-50-storybook.sh');
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // A theme other than a custom one never reaches the prompt, and the files
    // below are not covered by the theme's own token.
    if ($this->response === TRUE) {
      return;
    }

    $t = $this->tmpDir;
    $w = $this->webroot;

    File::remove([
      $t . '/scripts/provision-50-storybook.sh',
      $t . '/.docker/config/nginx/storybook.conf',
      $t . '/' . $w . '/sites/default/includes/modules/settings.storybook.php',
      $t . '/' . $w . '/sites/default/includes/modules/services.storybook.yml',
    ]);

    File::remove($this->themePaths('.storybook'));
    File::remove($this->themePaths('components/*/*.stories.twig'));

    JsonManipulator::updateFile($t . '/composer.json', function (JsonManipulator $cj): void {
      $cj->removeSubNode('require', 'drupal/storybook');
    });

    foreach ($this->themePaths('package.json') as $manifest) {
      JsonManipulator::updateFile($manifest, function (JsonManipulator $pj): void {
        $pj->removeSubNode('devDependencies', '@storybook/server-webpack5');
        $pj->removeSubNode('devDependencies', 'storybook');
        $pj->removeSubNode('scripts', 'storybook');
        $pj->removeSubNode('scripts', 'storybook-build');
      });

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
