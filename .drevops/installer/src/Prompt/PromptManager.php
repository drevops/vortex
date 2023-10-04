<?php

namespace DrevOps\Installer\Prompt;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Utils\ClassLoader;
use RuntimeException;
use Symfony\Component\Console\Style\SymfonyStyle;
use function Symfony\Component\String\u;

class PromptManager {

  protected $answers;

  /**
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  protected $io;

  protected $config;

  public function __construct(SymfonyStyle $io, Config $config) {
    $this->io = $io;
    $this->config = $config;
    // Always get a fresh bag of answers.
    $this->answers = Answers::getInstance()->clear();
  }

  public function askQuestions(mixed $callback) {
    // If the callback is set, invoke it
    if (!is_callable($callback)) {
      throw new RuntimeException('The questions callback must be set.');
    }

    call_user_func($callback, $this);

    return $this;
  }

  public function ask($question_id, $is_quiet = FALSE) {
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

  public function getAnswers() {
    return $this->answers;
  }

  public function getAnswer($question_id, $default = NULL) {
    return $this->answers->get($question_id, $default);
  }

  public function setAnswer($question_id, $value) {
    $this->answers->set($question_id, $value);

    return $this;
  }

  public function getAnswersSummary() {
    $values = [];

    $ids = array_keys($this->answers->getAll());
    foreach ($ids as $id) {
      $class = $this->getPromptClass($id);
      $values[$class::title()] = $class::getFormattedValue($this->answers->get($id));
    }

    return $values;
  }

  protected function getPromptClass($id): string {
    $classes = ClassLoader::load('Prompt', 'DrevOps\\Installer\\Prompt\\AbstractPrompt');

    $class = 'DrevOps\\Installer\\Prompt\\Concrete\\' . u($id)->camel()->title() . 'Prompt';

    if (!in_array($class, $classes) || !class_exists($class)) {
      throw new RuntimeException(sprintf('The prompt class "%s" does not exist.', $class));
    }

    return $class;
  }

}
