<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractPrompt;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Files;
use DrevOps\Installer\Utils\Strings;
use DrevOps\Installer\Utils\Validator;

/**
 *
 */
class NamePrompt extends AbstractPrompt {

  final const ID = 'name';

  /**
   * {@inheritdoc}
   */
  public static function title():string {
    return 'Site name';
  }

  /**
   * {@inheritdoc}
   */
  public static function question():string {
    return 'What is your site name?';
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    // Can be overridden by config variable.
    $value = $config->get(Env::PROJECT);
    if ($value) {
      return $value;
    }

    // From the destination composer.json.
    $value = Files::getComposerJsonValue('description', $config->getDstDir());

    if ($value && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', (string) $value, $matches) && !empty($matches[1])) {
      return $matches[1];
    }

    return basename($config->getDstDir());
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
