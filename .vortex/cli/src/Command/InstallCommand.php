<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Command;

use DrevOps\VortexCli\Downloader\Artifact;
use DrevOps\VortexCli\Downloader\Downloader;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;
use DrevOps\VortexCli\Prompts\Handlers\Starter;
use DrevOps\VortexCli\Prompts\InstallPresenter;
use DrevOps\VortexCli\Prompts\PromptManager;
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
 * Run command.
 *
 * Install command.
 *
 * @package DrevOps\VortexCli\Command
 */
class InstallCommand extends Command implements CommandRunnerAwareInterface, ExecutableFinderAwareInterface {

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
   * Defines default command name.
   *
   * @var string
   */
  public static $defaultName = 'install';

  /**
   * Defines the configuration object.
   */
  protected Config $config;

  /**
   * The prompt manager.
   */
  protected PromptManager $promptManager;

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
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('install');
    $this->setDescription('Install Vortex from remote or local repository.');
    $this->setHelp(<<<EOF
  <info>Interactively install Vortex from the latest stable release into the current directory:</info>
  php vortex.phar --destination=.

  <info>Non-interactively install Vortex from the latest stable release into the specified directory:</info>
  php vortex.phar --no-interaction --destination=path/to/destination

  <info>Install from the latest auto-discovered stable release (default behavior if --uri is specified):</info>
  php vortex.phar --uri=https://github.com/drevops/vortex.git
  php vortex.phar --uri=https://github.com/drevops/vortex.git#stable

  <info>Install using repository URL with specific git ref after #:</info>
  php vortex.phar --uri=https://github.com/drevops/vortex.git#25.11.0
  php vortex.phar --uri=https://github.com/drevops/vortex.git#v1.2.3
  php vortex.phar --uri=https://github.com/drevops/vortex.git#main

  <info>Copy GitHub URL directly from your browser:</info>
  php vortex.phar --uri=https://github.com/drevops/vortex/releases/tag/25.11.0
  php vortex.phar --uri=https://github.com/drevops/vortex/tree/1.2.3
  php vortex.phar --uri=https://github.com/drevops/vortex/tree/main
  php vortex.phar --uri=https://github.com/drevops/vortex/commit/abcd123
EOF
    );
    $this->addOption(static::OPTION_DESTINATION, NULL, InputOption::VALUE_REQUIRED, 'Destination directory. Defaults to the current directory.');
    $this->addOption(static::OPTION_ROOT, NULL, InputOption::VALUE_REQUIRED, 'Path to the root for file path resolution. If not specified, current directory is used.');
    $this->addOption(static::OPTION_NO_INTERACTION, 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question.');
    $this->addOption(static::OPTION_CONFIG, 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options or a path to a JSON file.');
    $this->addOption(static::OPTION_URI, 'l', InputOption::VALUE_REQUIRED, 'Remote or local repository URI with an optional git ref set after @.');
    $this->addOption(static::OPTION_NO_CLEANUP, NULL, InputOption::VALUE_NONE, 'Do not remove the CLI after successful installation.');
    $this->addOption(static::OPTION_BUILD, 'b', InputOption::VALUE_NONE, 'Run auto-build after installation without prompting.');
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

    try {
      OptionsResolver::checkRequirements($this->getExecutableFinder());
      [$this->config, $this->artifact] = OptionsResolver::resolve($input->getOptions());

      Tui::init($output, !$this->config->getNoInteraction());
      $this->promptManager = new PromptManager($this->config);
      $this->presenter = new InstallPresenter($this->config);
      $this->presenter->setPromptManager($this->promptManager);
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

      Tui::line(Tui::dim('Press any key to continue...'));
      Tui::getChar();

      $this->promptManager->runPrompts();

      // Flushed here rather than at resolve time because prompt answers are
      // read from the environment during the run above, so earlier reporting
      // would miss every deprecated prompt variable.
      $this->noticeDeprecatedEnvVars();

      Tui::list($this->promptManager->getResponsesSummary(), 'Installation summary');

      if (!$this->promptManager->shouldProceed()) {
        Tui::info('Aborting project installation. No files were changed.');

        return Command::SUCCESS;
      }

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
        action: fn() => $this->promptManager->runProcessors(),
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
      Tui::error('Installation failed with an error: ' . $exception->getMessage());

      return Command::FAILURE;
    }

    $this->presenter->footer();

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
        $this->presenter->footerBuildFailed();

        return Command::FAILURE;
      }

      $this->presenter->footerBuildSucceeded();
    }
    else {
      $this->presenter->footerBuildSkipped();
    }

    // Cleanup should take place only in case of the successful installation.
    // Otherwise, the user should be able to re-run the install.
    register_shutdown_function([$this, 'cleanup']);

    return Command::SUCCESS;
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
    $responses = $this->promptManager->getResponses();
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
