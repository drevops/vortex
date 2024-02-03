<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractPrompt;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Strings;
use DrevOps\Installer\Utils\Validator;

/**
 * Module prefix prompt.
 */
class ModulePrefixPrompt extends AbstractPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'module_prefix';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Module prefix';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'What is your project-specific module prefix?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return Strings::toAbbreviation($answers->get('machine_name', ''));
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    $webroot = $config->getWebroot();

    $locations = [
      $config->getDstDir() . sprintf('/%s/modules/custom/*_core', $webroot),
      $config->getDstDir() . sprintf('/%s/sites/all/modules/custom/*_core', $webroot),
      $config->getDstDir() . sprintf('/%s/profiles/*/modules/*_core', $webroot),
      $config->getDstDir() . sprintf('/%s/profiles/*/modules/custom/*_core', $webroot),
      $config->getDstDir() . sprintf('/%s/profiles/custom/*/modules/*_core', $webroot),
      $config->getDstDir() . sprintf('/%s/profiles/custom/*/modules/custom/*_core', $webroot),
    ];

    $value = Files::findMatchingPath($locations);

    if (!empty($value)) {
      $value = basename((string) $value);
      $value = str_replace('_core', '', $value);
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
