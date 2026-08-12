<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\PhpTui\Answers\Answers;
use DrevOps\PhpTui\Tui as Engine;
use DrevOps\VortexCli\Downloader\Artifact;
use DrevOps\VortexCli\Downloader\Downloader;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Form\VortexForm;
use DrevOps\VortexCli\Process\Processor;
use DrevOps\VortexCli\Prompts\Handlers\Starter;
use DrevOps\VortexCli\Prompts\InstallPresenter;
use DrevOps\VortexCli\Runner\CommandRunnerAwareInterface;
use DrevOps\VortexCli\Runner\CommandRunnerAwareTrait;
use DrevOps\VortexCli\Runner\ExecutableFinderAwareInterface;
use DrevOps\VortexCli\Runner\ExecutableFinderAwareTrait;
use DrevOps\VortexCli\Runner\RunnerInterface;
use DrevOps\VortexCli\Task\Task;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\Env;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Utils\FileManager;
use DrevOps\VortexCli\Utils\OptionsResolver;
use DrevOps\VortexCli\Utils\Tui;
use DrevOps\VortexCli\Utils\Version;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Shared machinery for the verbs that apply a downloaded template.
 *
 * Downloading the template, collecting answers, processing them and copying the
 * result into the destination is one flow. The verbs differ only in framing -
 * one starts from a version, the other points at a target one - so the flow
 * lives here and each verb stays a thin facade over it.
 *
 * @package DrevOps\VortexCli\Command
 */
abstract class AbstractInstallCommand extends Command implements CommandRunnerAwareInterface, ExecutableFinderAwareInterface {

  use CommandRunnerAwareTrait;
  use ExecutableFinderAwareTrait;
  use AgentSurfaceTrait;

  const OPTION_DESTINATION = 'destination';

  const OPTION_ROOT = 'root';

  const OPTION_NO_INTERACTION = 'no-interaction';

  const OPTION_CONFIG = 'config';

  const OPTION_QUIET = 'quiet';

  const OPTION_URI = 'uri';

  const OPTION_NO_CLEANUP = 'no-cleanup';

  const OPTION_BUILD = 'build';

  /**
   * The namespace the engine searches for handler classes.
   */
  protected const string HANDLER_NAMESPACE = 'DrevOps\\VortexCli\\Prompts\\Handlers';

  /**
   * Defines the configuration object.
   */
  protected Config $config;

  /**
   * The form engine collecting the answers.
   */
  protected Engine $tui;

  /**
   * The collected answers.
   */
  protected Answers $answers;

  /**
   * The processor applying the answers.
   */
  protected Processor $processor;

  /**
   * The install presenter.
   */
  protected InstallPresenter $presenter;

  /**
   * The file manager.
   */
  protected FileManager $fileManager;

  /**
   * The repository downloader.
   */
  protected ?RepositoryDownloader $repositoryDownloader = NULL;

  /**
   * The file downloader.
   */
  protected ?Downloader $fileDownloader = NULL;

  /**
   * The artifact representing the repository and reference to install.
   */
  protected Artifact $artifact;

  /**
   * Add the options shared by every template-applying verb.
   */
  protected function addCommonOptions(): void {
    $this->addOption(static::OPTION_DESTINATION, 'd', InputOption::VALUE_REQUIRED, 'Destination directory. Defaults to the current directory.');
    $this->addOption(static::OPTION_ROOT, NULL, InputOption::VALUE_REQUIRED, 'Path to the root for file path resolution. If not specified, current directory is used.');
    $this->addOption(static::OPTION_NO_INTERACTION, 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question.');
    $this->addOption(static::OPTION_CONFIG, 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options or a path to a JSON file.');
    $this->addOption(static::OPTION_URI, 'l', InputOption::VALUE_REQUIRED, 'Remote or local repository URI with an optional git ref set after #.');
    $this->addOption(static::OPTION_NO_CLEANUP, NULL, InputOption::VALUE_NONE, 'Do not remove the CLI after successful installation.');
    $this->addOption(static::OPTION_BUILD, 'b', InputOption::VALUE_NONE, 'Run auto-build after installation without prompting.');
    $this->addAgentSurfaceOptions();
  }

  /**
   * Download the template, collect answers, apply them and copy the result.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output.
   *
   * @return int
   *   The command exit code.
   */
  protected function doInstall(InputInterface $input, OutputInterface $output): int {
    if ($input->getOption('help')) {
      $output->write($this->getHelp());

      return Command::SUCCESS;
    }

    $agent_surface = $this->handleAgentSurface($input, $output);
    if ($agent_surface !== NULL) {
      return $agent_surface;
    }

    Tui::init($output);

    try {
      OptionsResolver::checkRequirements($this->getExecutableFinder());
      [$this->config, $this->artifact] = OptionsResolver::resolve($input->getOptions());

      Tui::init($output, !$this->config->getNoInteraction());
      $this->tui = new Engine(VortexForm::create($this->config), [static::HANDLER_NAMESPACE]);
      $this->processor = new Processor();
      $this->presenter = new InstallPresenter($this->config);
      $this->fileManager = new FileManager($this->config);

      $this->presenter->header($this->artifact, $this->getApplication()->getVersion());

      $this->assertMajorCompatibility();

      // Only validate if using custom repository or custom reference.
      if (!$this->artifact->isDefault()) {
        Task::action(
          label: 'Validating repository and reference',
          action: function (): string {
            $this->getRepositoryDownloader()->validate($this->artifact);
            return 'Repository and reference validated successfully';
          },
          hint: fn(): string => sprintf('Checking repository "%s" and reference "%s"', $this->artifact->getRepo(), $this->artifact->getRef()),
          success: fn(string $return): string => $return
        );
        Tui::line('');
      }

      $this->answers = $this->collectAnswers($input);
      $this->presenter->setAnswers($this->answers);

      // Flushed here rather than at resolve time because prompt answers are
      // read from the environment during the run above, so earlier reporting
      // would miss every deprecated prompt variable.
      $this->noticeDeprecatedEnvVars();

      Tui::box($this->answers->toSummary(), 'Installation summary');

      Tui::info('Starting project installation');

      Task::action(
        label: 'Downloading Vortex',
        action: function (): string {
          $release_prefix = Version::releasePrefix($this->getApplication()->getVersion());
          $version = $this->getRepositoryDownloader()->download($this->artifact, $this->config->get(Config::TMP), $release_prefix);
          $this->config->set(Config::VERSION, $version);
          return $version;
        },
        hint: fn(): string => sprintf('Downloading from "%s" repository at ref "%s"', $this->artifact->getRepo(), $this->artifact->getRef()),
        success: fn(string $return): string => sprintf('Vortex downloaded (%s)', $return)
      );

      Task::action(
        label: 'Customizing Vortex for your project',
        action: fn() => $this->processor->apply($this->answers, $this->tui->registry(), $this->config, VortexForm::PROCESSORS, VortexForm::WEIGHTS),
        success: 'Vortex was customized for your project',
      );

      Task::action(
        label: 'Preparing destination directory',
        action: fn(): array => $this->fileManager->prepareDestination(),
        success: 'Destination directory is ready',
      );

      Task::action(
        label: 'Copying files to the destination directory',
        action: fn() => $this->fileManager->copyFiles(),
        success: 'Files copied to destination directory',
      );

      Task::action(
        label: 'Preparing demo content',
        action: fn(): string|array => $this->fileManager->prepareDemo($this->getFileDownloader()),
        success: 'Demo content prepared',
      );
    }
    catch (\Exception $exception) {
      Tui::output()->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
      Tui::error(sprintf('%s failed with an error: %s', $this->operationName(), $exception->getMessage()));

      return Command::FAILURE;
    }

    if ($this->shouldGuide()) {
      $this->presenter->footer();
    }

    // Should build by default.
    $should_build = TRUE;
    // Requested build via `--build` option. Defaults to FALSE.
    $requested_build = (bool) $this->config->get(Config::BUILD_NOW);
    // Non-interactive: respect the `--build` option.
    if ($this->config->getNoInteraction()) {
      $should_build = $requested_build;
    }
    // Interactive: ask only if `--build` option was not provided.
    elseif (!$requested_build) {
      $should_build = Tui::confirm(
        label: 'Run the site build now?',
        default: (bool) Env::get('VORTEX_CLI_INSTALL_PROMPT_BUILD_NOW', TRUE),
        hint: 'Takes ~5-10 min; output will be streamed. You can skip and run later with: ahoy build',
      );
    }

    if ($should_build) {
      $build_ok = Task::action(
        label: 'Building site',
        action: fn(): bool => $this->runBuildCommand($output),
        streaming: TRUE,
      );

      if (!$build_ok) {
        // Printed on every path: it explains a non-zero exit code, so a
        // scripted caller needs it as much as a person does.
        $this->presenter->footerBuildFailed();

        return Command::FAILURE;
      }

      if ($this->shouldGuide()) {
        $this->presenter->footerBuildSucceeded();
      }
    }
    elseif ($this->shouldGuide()) {
      $this->presenter->footerBuildSkipped();
    }

    // Cleanup should take place only in case of the successful installation.
    // Otherwise, the user should be able to re-run the install.
    register_shutdown_function([$this, 'cleanup']);

    return Command::SUCCESS;
  }

  /**
   * The operation being performed, for reporting.
   *
   * Follows the destination the same way the header does, so a run cannot
   * announce itself as one operation and fail as another. A destination that
   * was never resolved has not been inspected yet, so it reads as an install.
   *
   * @return string
   *   The capitalised operation name.
   */
  protected function operationName(): string {
    return isset($this->config) && $this->config->isVortexProject() ? 'Update' : 'Installation';
  }

  /**
   * Whether closing guidance should be printed.
   *
   * Guidance is written for the person who just answered the questions. A
   * scripted run has no one to read it and its stdout belongs to the caller.
   *
   * @return bool
   *   TRUE when a person is watching.
   */
  protected function shouldGuide(): bool {
    return !$this->config->getNoInteraction();
  }

  /**
   * Collect the answers through the form.
   *
   * A terminal with nothing scripted at it gets the interactive form; a
   * scripted or piped run collects headlessly, so both behave the same.
   *
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input.
   *
   * @return \DrevOps\PhpTui\Answers\Answers
   *   The collected answers.
   */
  protected function collectAnswers(InputInterface $input): Answers {
    $prompts = $input->getOption(static::OPTION_PROMPTS);
    $prompts = is_string($prompts) ? $prompts : '';

    $destination = (string) $this->config->getDst();
    $update = (bool) $this->config->get(Config::IS_VORTEX_PROJECT);
    $version = (string) $this->getApplication()?->getVersion();
    $interactive = !$this->config->getNoInteraction() && $prompts === '';

    return $this->tui->run($prompts, $version, $destination, $interactive, $update);
  }

  /**
   * Run the 'build' command.
   *
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   The output interface.
   *
   * @return bool
   *   TRUE if the build command succeeded, FALSE otherwise.
   */
  protected function runBuildCommand(OutputInterface $output): bool {
    $responses = $this->answers->values;
    $starter = $responses[Starter::id()] ?? Starter::LOAD_DATABASE_DEMO;
    $is_profile = in_array($starter, [Starter::INSTALL_PROFILE_CORE, Starter::INSTALL_PROFILE_DRUPALCMS], TRUE);

    $args = ['--destination' => $this->config->getDst()];
    if ($is_profile) {
      $args['--profile'] = '1';
    }

    $runner = $this->getCommandRunner();
    $runner->run('build', args: $args, output: $output);

    return $runner->getExitCode() === RunnerInterface::EXIT_SUCCESS;
  }

  /**
   * Refuse to operate across major versions.
   *
   * Each CLI build serves a single major line. Updating an existing
   * project of a different major would cross a breaking boundary, so stop and
   * point the user at the matching CLI instead. Fresh installs and
   * projects whose major cannot be determined are treated as compatible.
   *
   * @throws \RuntimeException
   *   When the destination project's major differs from this CLI's major.
   */
  protected function assertMajorCompatibility(): void {
    if (!$this->config->isVortexProject()) {
      return;
    }

    $cli_major = Version::major($this->getApplication()->getVersion());
    if ($cli_major === NULL) {
      return;
    }

    $project_major = Version::detectProjectMajor((string) $this->config->getDst());
    if ($project_major === NULL || $project_major === $cli_major) {
      return;
    }

    throw new \RuntimeException(sprintf(
      'This Vortex CLI targets Vortex %1$d.x, but the destination is a Vortex %2$d.x project. Update it with the %2$d.x CLI instead: https://www.vortextemplate.com/v%2$d/install',
      $cli_major,
      $project_major,
    ));
  }

  /**
   * Report every superseded environment variable that supplied a value.
   */
  protected function noticeDeprecatedEnvVars(): void {
    foreach (Env::legacyUsed() as $current => $legacy) {
      Tui::note(sprintf('%s is deprecated and will be removed in a future release. Use %s instead.', $legacy, $current));
    }
  }

  /**
   * Clean up CLI artifacts.
   */
  public function cleanup(): void {
    // Skip cleanup if the no-cleanup flag is set.
    if ($this->config->get(Config::NO_CLEANUP, FALSE)) {
      return;
    }

    $phar_path = \Phar::running(FALSE);
    if (!empty($phar_path) && file_exists($phar_path)) {
      File::remove($phar_path);
    }
  }

  /**
   * Get the repository downloader.
   *
   * Provides a default RepositoryDownloader instance or returns the injected
   * one. This allows tests to inject mocks via setRepositoryDownloader().
   *
   * @return \DrevOps\VortexCli\Downloader\RepositoryDownloader
   *   The repository downloader.
   */
  protected function getRepositoryDownloader(): RepositoryDownloader {
    return $this->repositoryDownloader ??= new RepositoryDownloader();
  }

  /**
   * Set the repository downloader.
   *
   * @param \DrevOps\VortexCli\Downloader\RepositoryDownloader $repositoryDownloader
   *   The repository downloader.
   */
  public function setRepositoryDownloader(RepositoryDownloader $repositoryDownloader): void {
    $this->repositoryDownloader = $repositoryDownloader;
  }

  /**
   * Get the file downloader.
   *
   * Provides a default Downloader instance or returns the injected one.
   * This allows tests to inject mocks via setFileDownloader().
   *
   * @return \DrevOps\VortexCli\Downloader\Downloader
   *   The file downloader.
   */
  protected function getFileDownloader(): Downloader {
    return $this->fileDownloader ??= new Downloader();
  }

  /**
   * Set the file downloader.
   *
   * @param \DrevOps\VortexCli\Downloader\Downloader $fileDownloader
   *   The file downloader.
   */
  public function setFileDownloader(Downloader $fileDownloader): void {
    $this->fileDownloader = $fileDownloader;
  }

}
