<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Handler;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Utils\JsonManipulator;

/**
 * Handler for the "starter" question.
 *
 * @package DrevOps\VortexCli\Handler
 */
class Starter extends AbstractHandler implements OptionsInterface, FieldInterface {

  const INSTALL_PROFILE_CORE = 'install_profile_core';

  const INSTALL_PROFILE_DRUPALCMS = 'install_profile_drupalcms';

  const LOAD_DATABASE_DEMO = 'load_demodb';

  const INSTALL_PROFILE_DRUPALCMS_PATH = '../recipes/drupal_cms_starter';

  /**
   * {@inheritdoc}
   */
  public function process(Field $field, mixed $value, Context $context): void {
    if ($value !== 'install_profile_drupalcms') {
      return;
    }

    $cj = JsonManipulator::fromFile($context->directory . '/composer.json');

    if (!$cj instanceof JsonManipulator) {
      return;
    }

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

    file_put_contents($context->directory . '/composer.json', $cj->getContents());
  }

  /**
   * {@inheritdoc}
   */
  public static function options(): array {
    return [
      self::INSTALL_PROFILE_CORE => 'Drupal, installed from profile',
      self::INSTALL_PROFILE_DRUPALCMS => 'Drupal CMS, installed from profile',
      self::LOAD_DATABASE_DEMO => 'Drupal, loaded from the demo database',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function field(PanelBuilder $p): FieldBuilder {
    return $p->select('starter', 'How would you like your site to be created on the first run?')
      ->description('Applies only on the first run of the installer.')
      ->default(self::LOAD_DATABASE_DEMO)
      ->options(self::options())
      ->weight(250);
  }

}
