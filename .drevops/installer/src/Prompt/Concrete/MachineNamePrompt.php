<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractPrompt;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Strings;
use DrevOps\Installer\Utils\Validator;

/**
 * Machine name prompt.
 */
class MachineNamePrompt extends AbstractPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'machine_name';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Machine name';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'What is your site machine name?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return Strings::toMachineName($answers->get('name'));
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    $value = Files::getComposerJsonValue('name', $config->getDstDir());

    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
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
