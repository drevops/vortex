<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractConfirmationPrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Formatter;

/**
 * Preserve FTP prompt.
 */
class PreserveFtpPrompt extends AbstractConfirmationPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'preserve_ftp';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'FTP integration';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Do you want to keep FTP integration?';
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
    $value = DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::DB_DOWNLOAD_SOURCE);

    return is_null($value) ? NULL : $value == 'ftp';
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormattedValue(mixed $value): string {
    return Formatter::formatEnabledDisabled($value);
  }

}
