<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\Tui;

class Starter extends AbstractHandler {

  const DRUPAL_LOAD_DATABASE_DEMO = 'demodb';

  const DRUPAL_INSTALL_PROFILE = 'drupal_profile';

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    return 'How would you like your site to be created on the first run?';
  }

  /**
   * {@inheritdoc}
   */
  public static function description(array $responses): ?string {
    $label1 = Tui::bold('Drupal, installed from profile');
    $label11 = Tui::underscore('populating a fresh database');
    $label2 = Tui::bold('Drupal, loaded from the demo database');
    $label21 = Tui::underscore('loading an existing demo database');

    return <<<DOC
Choose how your site will be created the first time after this installer finishes:

    ○ {$label1}
      Creates a new site by {$label11}
      from one of the standard Drupal installation profiles.

    ○ {$label2}
      Creates a site by {$label21}
      provided with the installer.
DOC;
  }

  /**
   * {@inheritdoc}
   */
  public function hint(array $responses): ?string {
    return 'Use ⬆ and ⬇. Applies only on the first run of the installer.';
  }

  /**
   * {@inheritdoc}
   */
  public function options(array $responses): ?array {
    return [
      self::DRUPAL_INSTALL_PROFILE => 'Drupal, installed from profile',
      self::DRUPAL_LOAD_DATABASE_DEMO => 'Drupal, loaded from the demo database',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::DRUPAL_LOAD_DATABASE_DEMO;
  }

  /**
   * {@inheritdoc}
   */
  public function discover(): null|string|bool|array {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    // @todo Implement.
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall(): ?string {
    if ($this->isInstalled()) {
      return NULL;
    }

    $output = '';

    if ($this->response == self::DRUPAL_LOAD_DATABASE_DEMO) {
      $output .= 'Build project locally:' . PHP_EOL;
      $output .= '  ahoy build' . PHP_EOL;
      $output .= PHP_EOL;
    }
    elseif ($this->response == self::DRUPAL_INSTALL_PROFILE) {
      $output .= 'Build project locally:' . PHP_EOL;
      $output .= '  VORTEX_PROVISION_TYPE=profile ahoy build' . PHP_EOL;
      $output .= PHP_EOL;
      $output .= 'Export database:' . PHP_EOL;
      $output .= '  ahoy export-db db.sql' . PHP_EOL;
      $output .= PHP_EOL;
    }

    // @todo Update to use separate steps for hosting and CI/CD configuration.
    $output .= 'Setup integration with your hosting and CI/CD providers:' . PHP_EOL;
    $output .= '  See https://www.vortextemplate.com/docs/quickstart';
    $output .= PHP_EOL;

    return $output;
  }

}
