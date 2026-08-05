<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Prompts\PromptManager;
use DrevOps\VortexCli\Task\Task;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\OptionsResolver;
use DrevOps\VortexCli\Utils\Tui;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Configure command.
 *
 * Reconfigures an existing project in place, without downloading a template.
 * Answers are pre-filled from the project, and written back to it on --apply.
 *
 * @package DrevOps\VortexCli\Command
 */
class ConfigureCommand extends Command {

  use AgentSurfaceTrait;
  use DestinationAwareTrait;

  const OPTION_NO_INTERACTION = 'no-interaction';

  const OPTION_CONFIG = 'config';

  const OPTION_APPLY = 'apply';

  /**
   * Defines default command name.
   *
   * @var string
   */
  public static $defaultName = 'configure';

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('configure');
    $this->setDescription('Reconfigure an existing project in place.');
    $this->setHelp(<<<EOF
  <info>Collect answers for the current directory and print them without changing anything:</info>
  php vortex.phar configure

  <info>Collect answers and write them to the project:</info>
  php vortex.phar configure --apply

  <info>Reconfigure another directory without asking any question:</info>
  php vortex.phar configure --no-interaction --apply --destination=path/to/project

  <info>Answer up front and write the result:</info>
  php vortex.phar configure --apply --prompts='{"name":"My Project"}'

  Answers are pre-filled from the existing project. No template is downloaded,
  so the project is both the source the answers are read from and the tree they
  are written to.
EOF
    );
    $this->addDestinationOption();
    $this->addOption(static::OPTION_NO_INTERACTION, 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question.');
    $this->addOption(static::OPTION_CONFIG, 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options or a path to a JSON file.');
    $this->addOption(static::OPTION_APPLY, 'a', InputOption::VALUE_NONE, 'Write the collected answers to the project.');
    $this->addAgentSurfaceOptions();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if ($input->getOption('help')) {
      $output->write($this->getHelp());

      return Command::SUCCESS;
    }

    $agent_surface = $this->handleAgentSurface($input, $output);
    if ($agent_surface !== NULL) {
      return $agent_surface;
    }

    Tui::init($output);

    // Declared up front so the reporting below cannot depend on how far the
    // block underneath got before an exception.
    $apply = FALSE;
    $interactive = FALSE;

    try {
      $config = $this->resolveConfig($input);
      $interactive = !$config->getNoInteraction();

      Tui::init($output, $interactive);

      $apply = (bool) $input->getOption(static::OPTION_APPLY);

      if ($apply) {
        $this->assertVortexProject($config);
      }

      $prompt_manager = new PromptManager($config);
      $prompt_manager->runPrompts();

      if ($apply) {
        if ($interactive) {
          Tui::list($prompt_manager->getResponsesSummary(), 'Configuration summary');

          if (!$prompt_manager->shouldProceed(sprintf('These answers will be written to the project directory "%s"', $config->getDst()), 'Apply the answers to the project?')) {
            Tui::info('Aborting. No files were changed.');

            return Command::SUCCESS;
          }
        }

        $this->apply($prompt_manager, $interactive);
      }
      elseif ($interactive) {
        Tui::list($prompt_manager->getResponsesSummary(), 'Configuration summary');
      }
    }
    catch (\Exception $exception) {
      Tui::output()->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
      Tui::error('Configuration failed with an error: ' . $exception->getMessage());

      return Command::FAILURE;
    }

    if ($interactive) {
      $this->footer($apply);
    }
    else {
      // The answers are this command's data output: a scripted caller reads
      // them from stdout, so nothing else is written there.
      $output->writeln((string) json_encode($prompt_manager->getResponses(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    return Command::SUCCESS;
  }

  /**
   * Resolve the configuration for an in-place run.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return \DrevOps\VortexCli\Utils\Config
   *   The resolved configuration.
   */
  protected function resolveConfig(InputInterface $input): Config {
    // Resolved to an absolute path first: a relative "." would otherwise reach
    // basename() literally and derive a bogus default site name.
    $destination = $this->getDestination($input);

    $options = $input->getOptions();
    $options['destination'] = $destination;

    [$config] = OptionsResolver::resolve($options);

    // Nothing is downloaded, so the project is both the tree the handlers read
    // and the tree they write to. Set past the environment: an ambient working
    // directory variable must not redirect the write target.
    $config->set(Config::TMP, $destination, TRUE);

    // Version placeholders are stamped from the build rather than from a
    // downloaded release, since there is no release to take it from.
    $config->set(Config::VERSION, (string) $this->getApplication()?->getVersion(), TRUE);

    return $config;
  }

  /**
   * Refuse to write to a directory that does not hold a Vortex project.
   *
   * Applying answers rewrites files in place, so the target is confirmed as a
   * Vortex project before anything is written.
   *
   * @param \DrevOps\VortexCli\Utils\Config $config
   *   The resolved configuration.
   *
   * @throws \RuntimeException
   *   When the destination is not a Vortex project.
   */
  protected function assertVortexProject(Config $config): void {
    if ($config->isVortexProject()) {
      return;
    }

    throw new \RuntimeException(sprintf('"%s" is not a Vortex project, so there is nothing to reconfigure. Install Vortex into it first.', $config->getDst()));
  }

  /**
   * Write the collected answers to the project.
   *
   * @param \DrevOps\VortexCli\Prompts\PromptManager $prompt_manager
   *   The prompt manager holding the collected answers.
   * @param bool $interactive
   *   Whether a person is watching.
   */
  protected function apply(PromptManager $prompt_manager, bool $interactive): void {
    $action = fn() => $prompt_manager->runProcessors();

    if (!$interactive) {
      $action();

      return;
    }

    Task::action(
      label: 'Applying answers to the project',
      action: $action,
      success: 'Answers applied to the project',
    );
  }

  /**
   * Show what happened and what to do next.
   *
   * @param bool $applied
   *   Whether the answers were written to the project.
   */
  protected function footer(bool $applied): void {
    if (!$applied) {
      Tui::box('No files were changed. Re-run with --apply to write these answers to the project.', 'Finished collecting answers');

      return;
    }

    Tui::box('Please review the changes and commit the required files.', 'Finished configuring Vortex');
  }

}
