<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\InstallManager;
use DrevOps\Installer\Prompt\AbstractPrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Strings;
use DrevOps\Installer\Utils\Validator;

/**
 * Web root prompt.
 */
class WebrootPrompt extends AbstractPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'webroot';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Web root';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Web root (web, docroot)?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return 'web';
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    $webroot = DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::WEBROOT);

    if (empty($webroot) && InstallManager::isInstalled($config->getDstDir())) {
      // Try from composer.json.
      $extra = Files::getComposerJsonValue('extra', $config->getDstDir());
      if (!empty($extra)) {
        $webroot = $extra['drupal-scaffold']['drupal-scaffold']['locations']['web-root'] ?? NULL;
      }
    }

    return $webroot;
  }

  /**
   * {@inheritdoc}
   */
  protected function validator(mixed $value, Config $config, Answers $answers): void {
    Validator::machineName($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueNormalizer($value, Config $config, Answers $answers): mixed {
    return trim(Strings::toMachineName($value), '/');
  }

}
