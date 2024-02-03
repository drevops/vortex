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
 * Theme prompt.
 */
class ThemePrompt extends AbstractPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'theme';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Theme name';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'What is your theme machine name?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return $answers->get('machine_name', '');
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    if (InstallManager::isInstalled($config->getDstDir())) {
      $name = DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::DRUPAL_THEME);
      if (!empty($name)) {
        return $name;
      }
    }

    $webroot = $config->getWebroot();

    $locations = [
      $config->getDstDir() . sprintf('/%s/themes/custom/*/*.info', $webroot),
      $config->getDstDir() . sprintf('/%s/themes/custom/*/*.info.yml', $webroot),
      $config->getDstDir() . sprintf('/%s/sites/all/themes/custom/*/*.info', $webroot),
      $config->getDstDir() . sprintf('/%s/sites/all/themes/custom/*/*.info.yml', $webroot),
      $config->getDstDir() . sprintf('/%s/profiles/*/themes/custom/*/*.info', $webroot),
      $config->getDstDir() . sprintf('/%s/profiles/*/themes/custom/*/*.info.yml', $webroot),
      $config->getDstDir() . sprintf('/%s/profiles/custom/*/themes/custom/*/*.info', $webroot),
      $config->getDstDir() . sprintf('/%s/profiles/custom/*/themes/custom/*/*.info.yml', $webroot),
    ];

    $value = Files::findMatchingPath($locations);

    if (empty($value)) {
      return NULL;
    }

    if ($value !== '' && $value !== '0') {
      $value = basename((string) $value);
      $value = str_replace(['.info.yml', '.info'], '', $value);
    }

    return $value;
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
    return Strings::toMachineName($value);
  }

}
