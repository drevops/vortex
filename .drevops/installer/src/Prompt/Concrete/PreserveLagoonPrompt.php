<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractConfirmationPrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Formatter;

/**
 * Preserve FTP prompt.
 */
class PreserveLagoonPrompt extends AbstractConfirmationPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'preserve_lagoon';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Lagoon integration';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Do you want to keep Amazee.io Lagoon integration?';
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
    if (is_readable($config->getDstDir() . '/.lagoon.yml')) {
      return TRUE;
    }

    if ($answers->get('deploy_type') == DeployTypePrompt::CHOICE_LAGOON) {
      return TRUE;
    }

    $value = DotEnv::getValueFromDstDotenv($config->getDstDir(), 'LAGOON_PROJECT');

    // Special case - only work with non-empty value as 'LAGOON_PROJECT'
    // may not exist in installed site's .env file.
    if (empty($value)) {
      return NULL;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormattedValue(mixed $value): string {
    return Formatter::formatEnabledDisabled($value);
  }

}
