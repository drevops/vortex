<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractConfirmationPrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Formatter;

/**
 * Preserve Acquia prompt.
 */
class PreserveAcquiaPrompt extends AbstractConfirmationPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'preserve_acquia';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Acquia integration';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Do you want to keep Acquia Cloud integration?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    if (is_readable($config->getDstDir() . '/hooks')) {
      return TRUE;
    }

    $value = DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::DB_DOWNLOAD_SOURCE);

    return is_null($value) ? NULL : $value == 'acquia';
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormattedValue(mixed $value): string {
    return Formatter::formatEnabledDisabled($value);
  }

}
