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
 * Updates the project to a template version, re-applying the saved answers.
 *
 * Update always runs in update mode: it discovers the project's existing
 * answers, re-collects them (letting you change any) and re-applies. Choosing a
 * target template version to download - the "--to" selector - reuses the same
 * download machinery as the Install command and is wired in separately.
 *
 * @package DrevOps\VortexCli\Command
 */
class Update extends Command {

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
      ->setName('update')
      ->setDescription('Update the project to a template version, re-applying your answers.')
      ->addOption('to', NULL, InputOption::VALUE_REQUIRED, 'The target template version to update to.', '')
      ->addOption('dir', 'd', InputOption::VALUE_REQUIRED, 'The project directory.', '.')
      ->addOption('prompts', 'p', InputOption::VALUE_REQUIRED, 'Answers as a JSON string or a path to a JSON file.', '')
      ->addOption('apply', 'a', InputOption::VALUE_NONE, 'Apply the collected answers to the project directory.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $customizer = new Tui(VortexForm::create(), [static::HANDLER_NAMESPACE], static::ENV_PREFIX);

    $dir = $this->stringOption($input, 'dir');
    $resolved = realpath($dir);
    $dir = $resolved !== FALSE ? $resolved : $dir;

    $prompts = $this->stringOption($input, 'prompts');

    try {
      // Update always enables discovery so existing answers pre-fill the form.
      $answers = $customizer->collect($prompts, $dir, TRUE, $this->version());
    }
    catch (EngineException $engine_exception) {
      $output->writeln('<error>' . $engine_exception->getMessage() . '</error>');

      return Command::FAILURE;
    }

    if ($input->getOption('apply')) {
      (new Processor())->apply($customizer->config(), $customizer->registry(), $answers->values, new Context($dir, $answers->values, TRUE, $this->version(), $dir), VortexForm::PROCESSORS);
    }

    $output->writeln($answers->toJson());

    return Command::SUCCESS;
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
