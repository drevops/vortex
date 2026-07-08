<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\Tui\Tui;
use DrevOps\Tui\Engine\EngineException;
use DrevOps\Tui\Handler\Context;
use DrevOps\VortexCli\Form\VortexForm;
use DrevOps\VortexCli\Process\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Configures the project by driving the generic TUI engine.
 *
 * The CLI stays thin: it ships the configuration (the PHP `VortexForm`) and the
 * handler classes (auto-discovered by question id), then delegates collection,
 * conditionals, derivation, discovery and rendering to `drevops/tui`.
 *
 * @package DrevOps\VortexCli\Command
 */
class ConfigureCommand extends Command {

  /**
   * The namespace the engine searches for handler classes.
   */
  protected const HANDLER_NAMESPACE = 'DrevOps\\VortexCli\\Handler';

  /**
   * The version stamped into placeholders when the app version is unset.
   */
  protected const VERSION = '__VERSION__';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('configure')
      ->setDescription('Configure the project by answering questions.')
      ->addOption('prompts', 'p', InputOption::VALUE_REQUIRED, 'Answers as a JSON string or a path to a JSON file.', '')
      ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'The project directory.', '.')
      ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update an existing project (enable discovery).')
      ->addOption('schema', NULL, InputOption::VALUE_NONE, 'Print the question schema as JSON and exit.')
      ->addOption('validate', NULL, InputOption::VALUE_REQUIRED, 'Validate an answer set (JSON) against the schema and exit.', '')
      ->addOption('agent-help', NULL, InputOption::VALUE_NONE, 'Print instructions for driving the form non-interactively.')
      ->addOption('apply', 'a', InputOption::VALUE_NONE, 'Apply the collected answers to the project directory.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $tui = new Tui(VortexForm::create(), [static::HANDLER_NAMESPACE]);

    if ($input->getOption('schema')) {
      $output->writeln((string) json_encode($tui->schema()));

      return Command::SUCCESS;
    }

    if ($input->getOption('agent-help')) {
      $output->writeln($tui->agentHelp());

      return Command::SUCCESS;
    }

    $validate = $this->stringOption($input, 'validate');
    if ($validate !== '') {
      return $this->validateAnswers($tui, $validate, $output);
    }

    // Resolve to an absolute path so a relative "." does not reach basename()
    // literally downstream, which would derive a bogus default site name.
    $dir = $this->stringOption($input, 'dir');
    $resolved = realpath($dir);
    $dir = $resolved !== FALSE ? $resolved : $dir;

    $update = (bool) $input->getOption('update');
    $prompts = $this->stringOption($input, 'prompts');

    if (!$input->isInteractive() || $prompts !== '') {
      try {
        $answers = $tui->collect($prompts, $dir, $update, $this->version());
      }
      catch (EngineException $engine_exception) {
        $output->writeln('<error>' . $engine_exception->getMessage() . '</error>');

        return Command::FAILURE;
      }

      if ($input->getOption('apply')) {
        (new Processor())->apply($tui->config(), $tui->registry(), $answers->values, new Context($dir, $answers->values, $update, $this->version(), $dir), VortexForm::PROCESSORS);
      }

      $output->writeln($answers->toJson());

      return Command::SUCCESS;
    }

    // @codeCoverageIgnoreStart
    $answers = $tui->interact('', '', $this->version(), $dir);
    $output->writeln($answers->toJson());

    return Command::SUCCESS;
    // @codeCoverageIgnoreEnd
  }

  /**
   * Resolve the version string used to stamp version placeholders.
   *
   * @return string
   *   The application version, or the placeholder when it is unset.
   */
  protected function version(): string {
    $version = (string) $this->getApplication()?->getVersion();

    return $version === '' || $version === 'UNKNOWN' ? static::VERSION : $version;
  }

  /**
   * Validate a JSON answer set against the schema.
   *
   * @param \DrevOps\Tui\Tui $tui
   *   The TUI facade.
   * @param string $json
   *   The answer set as JSON.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return int
   *   The exit code.
   */
  protected function validateAnswers(Tui $tui, string $json, OutputInterface $output): int {
    $decoded = json_decode($json, TRUE);
    $answers = [];
    if (is_array($decoded)) {
      foreach ($decoded as $key => $value) {
        $answers[(string) $key] = $value;
      }
    }

    $errors = $tui->validate($answers);
    foreach ($errors as $error) {
      $output->writeln('<error>' . $error . '</error>');
    }

    if ($errors === []) {
      $output->writeln('The answer set is valid.');

      return Command::SUCCESS;
    }

    return Command::FAILURE;
  }

  /**
   * Read a string option, defaulting to empty.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param string $name
   *   The option name.
   *
   * @return string
   *   The option value.
   */
  protected function stringOption(InputInterface $input, string $name): string {
    $value = $input->getOption($name);

    return is_string($value) ? $value : '';
  }

}
