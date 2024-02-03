<?php

namespace DrevOps\Installer\Prompt;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Formatter;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Abstract confirmation prompt.
 */
abstract class AbstractConfirmationPrompt extends AbstractPrompt {

  /**
   * {@inheritdoc}
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueNormalizer($value, Config $config, Answers $answers): mixed {
    return (bool) $value;
  }

  /**
   * {@inheritdoc}
   */
  protected function createQuestion(string $text, mixed $default): Question {
    return new ConfirmationQuestion($text, (bool) $default);
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormattedValue(mixed $value): string {
    return Formatter::formatYesNo($value);
  }

}
