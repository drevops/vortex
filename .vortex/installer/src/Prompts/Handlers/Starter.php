<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts\Handlers;

use DrevOps\VortexInstaller\Utils\JsonManipulator;
use DrevOps\VortexInstaller\Utils\Tui;

class Starter extends AbstractHandler {

  const INSTALL_PROFILE_CORE = 'install_profile_core';

  const INSTALL_PROFILE_DRUPALCMS = 'install_profile_drupalcms';

  const LOAD_DATABASE_DEMO = 'load_demodb';

  const INSTALL_PROFILE_DRUPALCMS_PATH = '../recipes/drupal_cms_starter';

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

    $label2 = Tui::bold('Drupal CMS, installed from profile');
    $label21 = Tui::underscore('populating a fresh database');

    $label3 = Tui::bold('Drupal, loaded from the demo database');
    $label31 = Tui::underscore('loading an existing demo database');

    return <<<DOC
Choose how your site will be created the first time after this installer finishes:

    ○ {$label1}
      Creates a new site by {$label11}
      from one of the standard Drupal installation profiles.

    ○ {$label2}
      Creates a new site by {$label21}
      from the Drupal CMS recipe.

    ○ {$label3}
      Creates a site by {$label31}
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
      self::INSTALL_PROFILE_CORE => 'Drupal, installed from profile',
      self::INSTALL_PROFILE_DRUPALCMS => 'Drupal CMS, installed from profile',
      self::LOAD_DATABASE_DEMO => 'Drupal, loaded from the demo database',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function default(array $responses): null|string|bool|array {
    return self::LOAD_DATABASE_DEMO;
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
  public function shouldRun(array $responses): bool {
    return !$this->isInstalled();
  }

  /**
   * {@inheritdoc}
   */
  public function process(): void {
    $v = $this->getResponseAsString();
    $t = $this->tmpDir;

    if ($v === self::INSTALL_PROFILE_DRUPALCMS) {
      $cj = JsonManipulator::fromFile($t . '/composer.json');

      $cj->addLink('require', 'drupal/cms', '^1.2', TRUE);
      $cj->addLink('require', 'wikimedia/composer-merge-plugin', '^2.1', TRUE);
      $cj->addLink('require', 'symfony/http-client', '^6.4 || ^7.0', TRUE);

      $cj->addConfigSetting('allow-plugins.composer/installers', TRUE);
      $cj->addConfigSetting('allow-plugins.drupal/core-composer-scaffold', TRUE);
      $cj->addConfigSetting('allow-plugins.drupal/core-project-message', TRUE);
      $cj->addConfigSetting('allow-plugins.drupal/core-recipe-unpack', TRUE);
      $cj->addConfigSetting('allow-plugins.drupal/core-vendor-hardening', TRUE);
      $cj->addConfigSetting('allow-plugins.php-http/discovery', TRUE);
      $cj->addConfigSetting('allow-plugins.wikimedia/composer-merge-plugin', TRUE);

      $cj->addProperty('minimum-stability', 'alpha');

      $cj->addProperty('extra.merge-plugin.ignore-duplicates', FALSE);
      $cj->addProperty('extra.merge-plugin.merge-false', TRUE);
      $cj->addProperty('extra.merge-plugin.merge-extra', FALSE);
      $cj->addProperty('extra.merge-plugin.merge-extra-deep', FALSE);
      $cj->addProperty('extra.merge-plugin.merge-replace', TRUE);
      $cj->addProperty('extra.merge-plugin.merge-scripts', FALSE);
      $cj->addProperty('extra.merge-plugin.recurse', TRUE);
      $cj->addProperty('extra.merge-plugin.replace', TRUE);
      $cj->addProperty('extra.merge-plugin.require', ['vendor/drupal/cms/composer.json']);

      $c = $cj->getContents();
      file_put_contents($t . '/composer.json', $c);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postInstall(): ?string {
    if ($this->isInstalled()) {
      return NULL;
    }

    $output = '';

    if ($this->response == self::LOAD_DATABASE_DEMO) {
      $output .= 'Build project locally:' . PHP_EOL;
      $output .= '  ahoy build' . PHP_EOL;
      $output .= PHP_EOL;
    }
    elseif ($this->response == self::INSTALL_PROFILE_CORE || $this->response == self::INSTALL_PROFILE_DRUPALCMS) {
      $output .= 'Build project locally:' . PHP_EOL;
      $output .= '  VORTEX_PROVISION_TYPE=profile ahoy build' . PHP_EOL;
      $output .= PHP_EOL;
      $output .= 'Export database:' . PHP_EOL;
      $output .= '  ahoy export-db db.sql' . PHP_EOL;
      $output .= PHP_EOL;
    }

    // @todo Update to use separate steps for hosting and CI/CD configuration.
    $output .= 'Setup integration with your hosting and CI/CD providers:' . PHP_EOL;
    $output .= '  See https://www.vortextemplate.com/docs/getting-started/installation';

    return $output . PHP_EOL;
  }

}
