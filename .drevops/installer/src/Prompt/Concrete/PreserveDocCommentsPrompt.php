<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractConfirmationPrompt;
use DrevOps\Installer\Utils\Files;

/**
 * Preserve Acquia prompt.
 */
class PreserveDocCommentsPrompt extends AbstractConfirmationPrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'preserve_doc_comments';

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Preserve docs in comments';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'Do you want to keep detailed documentation in comments?';
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
    $file = $config->getDstDir() . '/.ahoy.yml';
    if (!is_readable($file)) {
      return NULL;
    }

    return Files::fileContains('Ahoy configuration file', $file);
  }

}
