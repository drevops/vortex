<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\Customizer\Customizer;
use DrevOps\Customizer\Engine\EngineException;
use DrevOps\Customizer\Handler\Context;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Customizes the project by driving the generic customizer engine.
 *
 * The CLI stays thin: it ships the configuration (`config/vortex.yml`) and the
 * handler classes (auto-discovered by question id), then delegates collection,
 * conditionals, derivation, discovery and rendering to `drevops/customizer`.
 *
 * @package DrevOps\VortexCli\Command
 */
class Customize extends Command {

  /**
   * The namespace the engine searches for handler classes.
   */
  protected const HANDLER_NAMESPACE = 'DrevOps\\VortexCli\\Handler';

  /**
   * The prefix for per-question environment variable overrides.
   */
  protected const ENV_PREFIX = 'VORTEX_';

  /**
   * The version stamped into placeholders when the app version is unset.
   */
  protected const VERSION = '__VERSION__';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this
      ->setName('customize')
      ->setDescription('Customize the project by answering questions.')
      ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to the configuration YAML.')
      ->addOption('prompts', 'p', InputOption::VALUE_REQUIRED, 'Answers as a JSON string or a path to a JSON file.', '')
      ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'The project directory.', '.')
      ->addOption('update', 'u', InputOption::VALUE_NONE, 'Update an existing project (enable discovery).')
      ->addOption('schema', NULL, InputOption::VALUE_NONE, 'Print the question schema as JSON and exit.')
      ->addOption('validate', NULL, InputOption::VALUE_REQUIRED, 'Validate an answer set (JSON) against the schema and exit.', '')
      ->addOption('agent-help', NULL, InputOption::VALUE_NONE, 'Print instructions for driving the customizer non-interactively.')
      ->addOption('apply', 'a', InputOption::VALUE_NONE, 'Apply the collected answers to the project directory.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $customizer = Customizer::fromFiles([$this->configPath($input)], [static::HANDLER_NAMESPACE], static::ENV_PREFIX);

    if ($input->getOption('schema')) {
      $output->writeln((string) json_encode($customizer->schema()));

      return Command::SUCCESS;
    }

    if ($input->getOption('agent-help')) {
      $output->writeln($customizer->agentHelp());

      return Command::SUCCESS;
    }

    $validate = $this->stringOption($input, 'validate');
    if ($validate !== '') {
      return $this->validateAnswers($customizer, $validate, $output);
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
        $answers = $customizer->collect($prompts, $dir, $update, $this->version());
      }
      catch (EngineException $engine_exception) {
        $output->writeln('<error>' . $engine_exception->getMessage() . '</error>');

        return Command::FAILURE;
      }

      if ($input->getOption('apply')) {
        $customizer->process($answers->values, new Context($dir, $answers->values, $update, $this->version(), $dir));
      }

      $output->writeln($answers->toJson());

      return Command::SUCCESS;
    }

    // @codeCoverageIgnoreStart
    $answers = $customizer->run('', '', $this->version(), $dir);
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
   * @param \DrevOps\Customizer\Customizer $customizer
   *   The customizer.
   * @param string $json
   *   The answer set as JSON.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return int
   *   The exit code.
   */
  protected function validateAnswers(Customizer $customizer, string $json, OutputInterface $output): int {
    $decoded = json_decode($json, TRUE);
    $answers = [];
    if (is_array($decoded)) {
      foreach ($decoded as $key => $value) {
        $answers[(string) $key] = $value;
      }
    }

    $errors = $customizer->validate($answers);
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
   * Resolve the configuration path (defaults to the bundled config).
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return string
   *   The configuration path.
   */
  protected function configPath(InputInterface $input): string {
    $path = $this->stringOption($input, 'config');

    return $path !== '' ? $path : __DIR__ . '/../../config/vortex.yml';
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
