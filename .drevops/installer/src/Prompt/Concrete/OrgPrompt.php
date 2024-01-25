<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractPrompt;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Strings;
use DrevOps\Installer\Utils\Validator;

class OrgPrompt extends AbstractPrompt {

  const ID = 'org';

  /**
   * {@inheritdoc}
   */
  public static function title():string {
    return 'Organisation';
  }

  /**
   * {@inheritdoc}
   */
  public static function question():string {
    return 'What is your organization name?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return $answers->get('name') ? $answers->get('name') . ' org' : '';
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    $value = Files::getComposerJsonValue('description', $config->getDstDir());

    if ($value && preg_match('/Drupal [0-9]+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', $value, $matches)) {
      if (!empty($matches[2])) {
        return $matches[2];
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function validator(mixed $value, Config $config, Answers $answers): void {
    Validator::humanName($value);
  }

  /**
   * {@inheritdoc}
   */
  protected function valueNormalizer($value, Config $config, Answers $answers): mixed {
    return ucfirst(Strings::toHumanName($value));
  }

}
