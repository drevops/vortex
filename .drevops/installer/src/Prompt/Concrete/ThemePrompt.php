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

class ThemePrompt extends AbstractPrompt {

  const ID = 'theme';

  /**
   * {@inheritdoc}
   */
  public static function title() {
    return 'Theme name';
  }

  /**
   * {@inheritdoc}
   */
  public static function question() {
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
      $config->getDstDir() . "/$webroot/themes/custom/*/*.info",
      $config->getDstDir() . "/$webroot/themes/custom/*/*.info.yml",
      $config->getDstDir() . "/$webroot/sites/all/themes/custom/*/*.info",
      $config->getDstDir() . "/$webroot/sites/all/themes/custom/*/*.info.yml",
      $config->getDstDir() . "/$webroot/profiles/*/themes/custom/*/*.info",
      $config->getDstDir() . "/$webroot/profiles/*/themes/custom/*/*.info.yml",
      $config->getDstDir() . "/$webroot/profiles/custom/*/themes/custom/*/*.info",
      $config->getDstDir() . "/$webroot/profiles/custom/*/themes/custom/*/*.info.yml",
    ];

    $value = Files::findMatchingPath($locations);

    if (empty($value)) {
      return NULL;
    }

    if ($value) {
      $value = basename($value);
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
