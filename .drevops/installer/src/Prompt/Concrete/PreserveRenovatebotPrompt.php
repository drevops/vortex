<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\InstallManager;
use DrevOps\Installer\Prompt\AbstractConfirmationPrompt;
use DrevOps\Installer\Utils\Formatter;

/**
 * Preserve Renovatebot prompt.
 */
class PreserveRenovatebotPrompt extends AbstractConfirmationPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'preserve_renovatebot';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Renovatebot integration';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Do you want to keep RenovateBot integration?';
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    if (!InstallManager::isInstalled($config->getDstDir())) {
      return NULL;
    }

    return is_readable($config->getDstDir() . '/renovate.json');
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormattedValue(mixed $value): string {
    return Formatter::formatEnabledDisabled($value);
  }

}
