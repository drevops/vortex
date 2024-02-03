<?php

namespace DrevOps\Installer\Prompt;

use DrevOps\Installer\Bag\AbstractBag;
use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\ClassLoader;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Symfony\Component\String\u;

/**
 * Prompt manager.
 */
class PromptManager {

  /**
   * A bag of answers.
   */
  protected AbstractBag $answers;

  /**
   * Prompt manager constructor.
   *
   * @param \Symfony\Component\Console\Style\SymfonyStyle $io
   *   The Symfony style.
   * @param \DrevOps\Installer\Bag\Config $config
   *   The config.
   */
  public function __construct(protected SymfonyStyle $io, protected Config $config) {
    // Always get a fresh bag of answers.
    $this->answers = Answers::getInstance()->clear();
  }

  /**
   * Ask questions.
   *
   * @param mixed $callback
   *   The callback to ask questions.
   *
   * @return $this
   *   The prompt manager.
   */
  public function askQuestions(mixed $callback): static {
    // If the callback is set, invoke it.
    if (!is_callable($callback)) {
      throw new \RuntimeException('The questions callback must be set.');
    }

    call_user_func($callback, $this);

    return $this;
  }

  /**
   * Ask a question.
   *
   * @param string $question_id
   *   The question ID.
   * @param bool $is_quiet
   *   Whether to ask the question quietly.
   *
   * @return mixed
   *   The answer.
   */
  public function ask(string $question_id, $is_quiet = FALSE) {
    $class = $this->getPromptClass($question_id);

    // Allow to override asking the questions verbosely to re-use the same
    // prompt processing logic.
    if ($is_quiet) {
      // @todo Implement this.
    }

    $answer = (new $class($this->io))->ask($this->config, $this->answers);
    $this->answers->set($question_id, $answer);

    return $answer;
  }

  /**
   * Get the answers.
   *
   * @return \DrevOps\Installer\Bag\AbstractBag
   *   The answers bag.
   */
  public function getAnswers(): AbstractBag {
    return $this->answers;
  }

  /**
   * Get an answer.
   *
   * @param string $question_id
   *   The question ID.
   * @param mixed $default
   *   The default value.
   *
   * @return mixed
   *   The answer.
   */
  public function getAnswer(string $question_id, mixed $default = NULL) {
    return $this->answers->get($question_id, $default);
  }

  /**
   * Set an answer.
   *
   * @param string $question_id
   *   The question ID.
   * @param mixed $value
   *   The value.
   *
   * @return $this
   *   The prompt manager.
   */
  public function setAnswer(string $question_id, mixed $value): static {
    $this->answers->set($question_id, $value);

    return $this;
  }

  /**
   * Get the answers summary.
   *
   * @return mixed[]
   *   The answers summary.
   */
  public function getAnswersSummary(): array {
    $values = [];

    $ids = array_keys($this->answers->getAll());
    foreach ($ids as $id) {
      $class = $this->getPromptClass($id);
      $values[$class::title()] = $class::getFormattedValue($this->answers->get($id));
    }

    return $values;
  }

  /**
   * Get the prompt class.
   *
   * @param string|null $id
   *   The prompt ID.
   *
   * @return string
   *   The prompt class.
   */
  protected function getPromptClass(?string $id): string {
    $classes = ClassLoader::load('Prompt', AbstractPrompt::class);

    $class = 'DrevOps\\Installer\\Prompt\\Concrete\\' . u($id)->camel()->title() . 'Prompt';

    if (!in_array($class, $classes) || !class_exists($class)) {
      throw new \RuntimeException(sprintf('The prompt class "%s" does not exist.', $class));
    }

    return $class;
  }

}
