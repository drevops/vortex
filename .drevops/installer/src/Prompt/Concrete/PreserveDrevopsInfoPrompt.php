<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractConfirmationPrompt;
use DrevOps\Installer\Utils\Files;

/**
 * Preserve Acquia prompt.
 */
class PreserveDrevopsInfoPrompt extends AbstractConfirmationPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'preserve_drevops_info';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Preserve DrevOps comments';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Do you want to keep all DrevOps information?';
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
    $file = $config->getDstDir() . '/.ahoy.yml';
    if (!is_readable($file)) {
      return NULL;
    }

    return Files::fileContains('Comments starting with', $file);
  }

}
