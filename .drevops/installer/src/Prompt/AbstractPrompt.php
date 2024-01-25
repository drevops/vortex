<?php

namespace DrevOps\Installer\Prompt;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use Exception;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractPrompt {

  public function __construct(
    /**
     * The IO interface.
     */
    protected SymfonyStyle $io
  ) {
  }

  public function ask(Config $config, Answers $answers) {
    $default = $this->compileDefaultValue($config, $answers);
    $text = static::getFormattedQuestion($default);

    // Symfony's implementation of normalizers and validators is a bit
    // confusing. The normalizer is called on direct user input, while the
    // validator is called on both direct user input and non-interactive.
    // If the answer is not what is expected to be stored, like with a Choice
    // question where the answer is a key rather than value, there is no way
    // to "normalize" such answer (there simply no method in the Question
    // class). So we need to introduce such "value normalizer" here. Moreover,
    // we have to inject it into the validator to make sure that each validator
    // for the descendant classes will use a "normalized value" for validation,
    // but we cannot return the normalized value from the validator per
    // Symfony's specification, so the value normaliser would need to be called
    // again in ::processAnswer() for the implementing class.
    $question = $this->createQuestion($text, $default)
      // Normaliser works on direct user input. It is not called on
      // non-interactive invocation.
      ->setNormalizer(function ($value) use ($config, $answers) {
        return $this->normalizer($value, $config, $answers);
      })
      // Validator runs on both interactive and non-interactive invocations.
      ->setValidator(function ($value) use ($config, $answers) {
        // Normalise the answer before validation to get the expected value.
        $normalised_value = $this->valueNormalizer($value, $config, $answers);

        $this->validator($normalised_value, $config, $answers);

        return $value;
      });

    $this->processQuestion($question, $config, $answers);

    $answer = $this->io->askQuestion($question);

    return $this->processAnswer($answer, $config, $answers);
  }

  /**
   * The question title.
   *
   * @return string
   *  The question title.
   */
  public static function title(): string {
    throw new Exception('The question title is not defined.');
  }

  /**
   * The question text.
   *
   * @return string
   *  The question text.
   */
  public static function question(): string {
    throw new Exception('The question text is not defined.');
  }


  /**
   * Get the formatted question.
   *
   * @param mixed $default
   *  The default value.
   *
   * @return string
   *  The formatted question.
   */
  public static function getFormattedQuestion(mixed $default): string {
    return static::question();
  }

  /**
   * Formatted value printed on the screen.
   *
   * @param mixed $value
   *  The value to be printed.
   *
   * @return string
   *  The formatted value.
   */
  public static function getFormattedValue(mixed $value): string {
    return $value;
  }

  /**
   * The default value.
   *
   * This is the "true" default value: it is a scalar based on sensible defaults
   * or previous answers. It is not discovered from the environment in any way.
   * It does not run through validation or normalisation. discoveredValue(),
   * on another hand, is discovered from the environment and runs through
   * validation and normalisation.
   *
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config bag.
   * @param \DrevOps\Installer\Bag\Answers $answers
   *   The answers bag.
   *
   * @return mixed
   *   The default value.
   */
  protected function defaultValue(Config $config, Answers $answers): mixed {
    return NULL;
  }

  /**
   * The discovered value.
   *
   * Discovered means that the value is discovered from the environment.
   *
   * Value discoveries should return NULL if they don't have the resources to
   * discover a value. This means that if the value is expected to come from a
   * file but the file is not available, the function should return NULL instead
   * of a falsy value like FALSE or 0.
   *
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config bag.
   * @param \DrevOps\Installer\Bag\Answers $answers
   *   The answers bag.
   *
   * @return mixed
   *  The discovered value.
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    return NULL;
  }

  /**
   * The normalizer.
   *
   * Input normaliser is used to normalise the answer for interactive prompt.
   * It is not used for non-interactive invocation.
   *
   * Trimming the user import is already done by Symfony's QuestionHelper
   * outside of the normalizer.
   *
   * @param mixed $value
   *   The value to normalize.
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config bag.
   * @param \DrevOps\Installer\Bag\Answers $answers
   *   The answers bag.
   *
   * @return mixed
   *  The normalized value.
   */
  protected function normalizer(mixed $value, Config $config, Answers $answers): mixed {
    return $value;
  }

  /**
   * The validator.
   *
   * Validator is used to validate the answer.
   *
   * @param mixed $value
   *   The value to validate.
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config bag.
   * @param \DrevOps\Installer\Bag\Answers $answers
   *   The answers bag.
   */
  protected function validator(mixed $value, Config $config, Answers $answers): void {
  }

  /**
   * The value normalizer.
   *
   * Normalise the answer before validation to get the expected value.
   * It runs before the value is passed to the validator.
   *
   * @param mixed $value
   *   The value to normalize.
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config bag.
   * @param \DrevOps\Installer\Bag\Answers $answers
   *   The answers bag.
   *
   * @return mixed
   *  The normalized value.
   */
  protected function valueNormalizer(mixed $value, Config $config, Answers $answers): mixed {
    return $value;
  }

  /**
   * Process the question instance before asking.
   *
   * @param \Symfony\Component\Console\Question\Question $question
   *   The question instance.
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config bag.
   * @param \DrevOps\Installer\Bag\Answers $answers
   *   The answers bag.
   */
  protected function processQuestion(Question $question, Config $config, Answers $answers): void {
  }

  /**
   * Process answer after receiving it from the question instance.
   *
   * @param mixed $value
   *   The answer.
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config bag.
   * @param \DrevOps\Installer\Bag\Answers $answers
   *   The answers bag.
   *
   * @return mixed
   *   Processed answer.
   */
  protected function processAnswer(mixed $value, Config $config, Answers $answers): mixed {
    return $this->valueNormalizer($value, $config, $answers);
  }

  /**
   * Create the question instance.
   *
   * Child instances can override this method to provide custom question types.
   *
   * @param string $text
   *  The question text.
   * @param mixed $default
   *  The default value.
   *
   * @return \Symfony\Component\Console\Question\Question
   *  The question.
   */
  protected function createQuestion(string $text, mixed $default): Question {
    $default = is_array($default) ? implode(',', $default) : $default;

    return new Question($text, $default);
  }

  /**
   * Get the default value.
   *
   * The value will be discovered first, and if not found, the default value
   * will be used.
   * Discovery means that the value is taken from the environment (variables or
   * file system).
   *
   * The value will be validated and normalized.
   *
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config bag.
   * @param \DrevOps\Installer\Bag\Answers $answers
   *   The answers bag.
   *
   * @return mixed
   *   The default value.
   */
  protected function compileDefaultValue(Config $config, Answers $answers): mixed {
    // Both normalise and validate, as defaults could be coming from the
    // user input based on the environment (variables, directory names etc.).
    $default = $this->defaultValue($config, $answers);

    $discovered = $this->discoveredValue($config, $answers);
    $discovered = $this->normalizer($discovered, $config, $answers);

    if ($discovered) {
      try {
        $discovered = $this->valueNormalizer($discovered, $config, $answers);
        $this->validator($discovered, $config, $answers);
      }
      catch (Exception) {
        $discovered = NULL;
      }
    }

    return $discovered ?: $default;
  }

}
