<?php

namespace DrevOps\Installer\Prompt;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\Arrays;
use DrevOps\Installer\Utils\Strings;
use DrevOps\Installer\Utils\Validator;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Abstract class for choice prompts.
 */
abstract class AbstractChoicePrompt extends AbstractPrompt {

  /**
   * Whether the prompt is multiselect.
   *
   * @var bool
   */
  protected $isMultiselect = FALSE;

  /**
   * Returns the choices.
   *
   * @return array
   *   The choices array with keys as shortopts and values as longopts.
   */
  abstract public static function choices(): array;

  /**
   * {@inheritdoc}
   *
   * Unlike the parent method, this method returns the default value as a key.
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    $choices = self::makeChoicesReindex($this->choices());

    return key($choices);
  }

  /**
   * {@inheritdoc}
   */
  protected function validator(mixed $value, Config $config, Answers $answers): void {
    $value = is_array($value) ? $value : [$value];

    Validator::notEmpty($value);

    foreach ($value as $v) {
      Validator::machineName($v);
      Validator::inList(array_values($this->choices()), $v);
    }

    if (!$this->isMultiselect && count($value) > 1) {
      throw new \InvalidArgumentException('Only one value is allowed.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueNormalizer(mixed $value, Config $config, Answers $answers): mixed {
    $value = is_array($value) ? $value : array_map('trim', explode(',', (string) $value));
    $value = array_filter($value);

    if (empty($value)) {
      return NULL;
    }

    $choices = self::makeChoicesReindex($this->choices());

    // Find values from longopts and shortops.
    $updated_value = [];
    foreach ($value as $v) {
      // Value is longopt.
      if (in_array($v, $choices)) {
        $updated_value[] = $v;
      }
      // Value is a shortopt.
      elseif (isset($choices[$v])) {
        $updated_value[] = $choices[$v];
      }
    }

    if (!$this->isMultiselect) {
      $updated_value = empty($updated_value) ? NULL : reset($updated_value);
    }
    elseif (is_array($updated_value)) {
      $updated_value = array_unique($updated_value);
      $updated_value = Arrays::sortByValueArray($updated_value, $choices);
    }

    return $updated_value;
  }

  /**
   * {@inheritdoc}
   */
  protected function createQuestion(string $text, mixed $default): Question {
    $question = new ChoiceQuestion($text, self::makeChoicesReindex($this->choices()), Strings::listToString($default));

    if ($this->isMultiselect) {
      $question->setMultiselect(TRUE);
    }

    return $question;
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormattedValue(mixed $value): string {
    return is_array($value) ? Strings::listToString($value) : $value;
  }

  /**
   * Re-indexes the choices array with keys starting from value.
   *
   * @param array $choices
   *   The choices array with longopts.
   *
   * @return array
   *   The choices array with re-indexed keys and values as longopts.
   */
  protected static function makeChoicesReindex(array $choices): array {
    return Arrays::reindex($choices, 1);
  }

  public function isMultiselect(): bool {
    return $this->isMultiselect;
  }

  public function setIsMultiselect(bool $isMultiselect): void {
    $this->isMultiselect = $isMultiselect;
  }

}
