<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Command;

use DrevOps\VortexInstaller\Downloader\Artifact;
use DrevOps\VortexInstaller\Downloader\Downloader;
use DrevOps\VortexInstaller\Downloader\RepositoryDownloader;
use DrevOps\VortexInstaller\Prompts\InstallerPresenter;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Runner\CommandRunnerAwareInterface;
use DrevOps\VortexInstaller\Runner\CommandRunnerAwareTrait;
use DrevOps\VortexInstaller\Runner\ExecutableFinderAwareInterface;
use DrevOps\VortexInstaller\Runner\ExecutableFinderAwareTrait;
use DrevOps\VortexInstaller\Runner\RunnerInterface;
use DrevOps\VortexInstaller\Schema\AgentHelp;
use DrevOps\VortexInstaller\Schema\ConfigValidator;
use DrevOps\VortexInstaller\Schema\SchemaGenerator;
use DrevOps\VortexInstaller\Task\Task;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Run command.
 *
 * Install command.
 *
 * @package DrevOps\VortexInstaller\Command
 */
class InstallCommand extends Command implements CommandRunnerAwareInterface, ExecutableFinderAwareInterface {

  use CommandRunnerAwareTrait;
  use ExecutableFinderAwareTrait;

  const OPTION_DESTINATION = 'destination';

  const OPTION_ROOT = 'root';

  const OPTION_NO_INTERACTION = 'no-interaction';

  const OPTION_CONFIG = 'config';

  const OPTION_QUIET = 'quiet';

  const OPTION_URI = 'uri';

  const OPTION_NO_CLEANUP = 'no-cleanup';

  const OPTION_BUILD = 'build';

  const OPTION_SCHEMA = 'schema';

  const OPTION_VALIDATE = 'validate';

  const OPTION_AGENT_HELP = 'agent-help';

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
   * The installer presenter.
   */
  protected InstallerPresenter $presenter;

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
  php installer.php --destination=.

  <info>Non-interactively install Vortex from the latest stable release into the specified directory:</info>
  php installer.php --no-interaction --destination=path/to/destination

  <info>Install from the latest auto-discovered stable release (default behavior if --uri is specified):</info>
  php installer.php --uri=https://github.com/drevops/vortex.git
  php installer.php --uri=https://github.com/drevops/vortex.git#stable

  <info>Install using repository URL with specific git ref after #:</info>
  php installer.php --uri=https://github.com/drevops/vortex.git#25.11.0
  php installer.php --uri=https://github.com/drevops/vortex.git#v1.2.3
  php installer.php --uri=https://github.com/drevops/vortex.git#main

  <info>Copy GitHub URL directly from your browser:</info>
  php installer.php --uri=https://github.com/drevops/vortex/releases/tag/25.11.0
  php installer.php --uri=https://github.com/drevops/vortex/tree/1.2.3
  php installer.php --uri=https://github.com/drevops/vortex/tree/main
  php installer.php --uri=https://github.com/drevops/vortex/commit/abcd123
EOF
    );
    $this->addOption(static::OPTION_DESTINATION, NULL, InputOption::VALUE_REQUIRED, 'Destination directory. Defaults to the current directory.');
    $this->addOption(static::OPTION_ROOT, NULL, InputOption::VALUE_REQUIRED, 'Path to the root for file path resolution. If not specified, current directory is used.');
    $this->addOption(static::OPTION_NO_INTERACTION, 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question.');
    $this->addOption(static::OPTION_CONFIG, 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options or a path to a JSON file.');
    $this->addOption(static::OPTION_URI, 'l', InputOption::VALUE_REQUIRED, 'Remote or local repository URI with an optional git ref set after @.');
    $this->addOption(static::OPTION_NO_CLEANUP, NULL, InputOption::VALUE_NONE, 'Do not remove installer after successful installation.');
    $this->addOption(static::OPTION_BUILD, 'b', InputOption::VALUE_NONE, 'Run auto-build after installation without prompting.');
    $this->addOption(static::OPTION_SCHEMA, NULL, InputOption::VALUE_NONE, 'Output prompt schema as JSON.');
    $this->addOption(static::OPTION_VALIDATE, NULL, InputOption::VALUE_NONE, 'Validate config without installing.');
    $this->addOption(static::OPTION_AGENT_HELP, NULL, InputOption::VALUE_NONE, 'Output instructions for AI agents on how to use the installer.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    if ($input->getOption('help')) {
      $output->write($this->getHelp());

      return Command::SUCCESS;
    }

    if ($input->getOption(static::OPTION_AGENT_HELP)) {
      return $this->handleAgentHelp($output);
    }

    if ($input->getOption(static::OPTION_SCHEMA)) {
      return $this->handleSchema($input, $output);
    }

    if ($input->getOption(static::OPTION_VALIDATE)) {
      return $this->handleValidate($input, $output);
    }

    Tui::init($output);

    try {
      $this->checkRequirements();
      $this->resolveOptions($input->getArguments(), $input->getOptions());

      Tui::init($output, !$this->config->getNoInteraction());
      $this->promptManager = new PromptManager($this->config);
      $this->presenter = new InstallerPresenter($this->config);
      $this->presenter->setPromptManager($this->promptManager);

      $this->presenter->header($this->artifact, $this->getApplication()->getVersion());

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

      Tui::list($this->promptManager->getResponsesSummary(), 'Installation summary');

      if (!$this->promptManager->shouldProceed()) {
        Tui::info('Aborting project installation. No files were changed.');

        return Command::SUCCESS;
      }

      Tui::info('Starting project installation');

      Task::action(
        label: 'Downloading Vortex',
        action: function (): string {
          $version = $this->getRepositoryDownloader()->download($this->artifact, $this->config->get(Config::TMP));
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
        action: fn(): array => $this->prepareDestination(),
        success: 'Destination directory is ready',
      );

      Task::action(
        label: 'Copying files to the destination directory',
        action: fn() => $this->copyFiles(),
        success: 'Files copied to destination directory',
      );

      Task::action(
        label: 'Preparing demo content',
        action: fn(): string|array => $this->prepareDemo(),
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
        default: (bool) Env::get('VORTEX_INSTALLER_PROMPT_BUILD_NOW', TRUE),
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
    // Otherwise, the user should be able to re-run the installer.
    register_shutdown_function([$this, 'cleanup']);

    return Command::SUCCESS;
  }

  /**
   * Handle --schema option.
   */
  protected function handleSchema(InputInterface $input, OutputInterface $output): int {
    $config = Config::fromString('{}');
    $prompt_manager = new PromptManager($config);

    $generator = new SchemaGenerator();
    $schema = $generator->generate($prompt_manager->getHandlers());

    $output->write((string) json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return Command::SUCCESS;
  }

  /**
   * Handle --validate option.
   */
  protected function handleValidate(InputInterface $input, OutputInterface $output): int {
    $config_option = $input->getOption(static::OPTION_CONFIG);

    if (empty($config_option) || !is_string($config_option)) {
      $output->writeln('The --validate option requires --config.');

      return Command::FAILURE;
    }

    $config_json = is_file($config_option) ? (string) file_get_contents($config_option) : $config_option;
    $user_config = json_decode($config_json, TRUE);

    if (!is_array($user_config)) {
      $output->writeln('Invalid JSON in --config.');

      return Command::FAILURE;
    }

    $config = Config::fromString('{}');
    $prompt_manager = new PromptManager($config);

    $validator = new ConfigValidator();
    $result = $validator->validate($user_config, $prompt_manager->getHandlers());

    $output->write((string) json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    return $result['valid'] ? Command::SUCCESS : Command::FAILURE;
  }

  /**
   * Handle --agent-help option.
   *
   * Outputs instructions for AI agents on how to use the installer
   * programmatically via --schema and --validate.
   */
  protected function handleAgentHelp(OutputInterface $output): int {
    $output->write(AgentHelp::render());

    return Command::SUCCESS;
  }

  protected function checkRequirements(): void {
    $required_commands = [
      'git',
      'tar',
      'composer',
    ];

    foreach ($required_commands as $required_command) {
      if ($this->getExecutableFinder()->find($required_command) === NULL) {
        throw new \RuntimeException(sprintf('Missing required command: %s.', $required_command));
      }
    }
  }

  /**
   * Instantiate configuration from CLI options and environment variables.
   *
   * Installer configuration is a set of internal installer variables
   * prefixed with "VORTEX_INSTALLER_" and used to control the installation.
   * They are read from the environment variables with $this->config->get().
   *
   * For simplicity of naming, internal installer config variables used in
   * $this->config->get() match environment variables names.
   *
   * @param array<mixed> $arguments
   *   Array of CLI arguments.
   * @param array<mixed> $options
   *   Array of CLI options.
   */
  protected function resolveOptions(array $arguments, array $options): void {
    $config = '{}';
    if (isset($options[static::OPTION_CONFIG]) && is_scalar($options[static::OPTION_CONFIG])) {
      $config_candidate = strval($options[static::OPTION_CONFIG]);
      $config = is_file($config_candidate) ? (string) file_get_contents($config_candidate) : $config_candidate;
    }

    $this->config = Config::fromString($config);

    $this->config->setQuiet($options[static::OPTION_QUIET]);
    $this->config->setNoInteraction($options[static::OPTION_NO_INTERACTION]);

    // Set root directory to resolve relative paths.
    $root = !empty($options[static::OPTION_ROOT]) && is_scalar($options[static::OPTION_ROOT]) ? strval($options[static::OPTION_ROOT]) : NULL;
    if ($root) {
      $this->config->set(Config::ROOT, $root);
    }

    // Set destination directory.
    $dst_from_option = !empty($options[static::OPTION_DESTINATION]) && is_scalar($options[static::OPTION_DESTINATION]) ? strval($options[static::OPTION_DESTINATION]) : NULL;
    $dst_from_env = Env::get(Config::DST);
    $dst_from_config = $this->config->get(Config::DST);
    $dst_from_root = $this->config->get(Config::ROOT);

    $dst = $dst_from_option ?: ($dst_from_env ?: ($dst_from_config ?: $dst_from_root));
    $dst = File::realpath($dst);
    $this->config->set(Config::DST, $dst, TRUE);

    // Load values from the destination .env file, if it exists.
    $dest_env_file = $this->config->getDst() . '/.env';

    if (File::exists($dest_env_file)) {
      Env::putFromDotenv($dest_env_file);
    }

    // Build URI for artifact.
    $uri_from_option = !empty($options[static::OPTION_URI]) && is_scalar($options[static::OPTION_URI]) ? strval($options[static::OPTION_URI]) : NULL;
    $repo = Env::get(Config::REPO) ?: ($this->config->get(Config::REPO) ?: NULL);
    $ref = Env::get(Config::REF) ?: ($this->config->get(Config::REF) ?: NULL);

    // Priority: option URI > env/config repo+ref > default.
    $uri = $uri_from_option;
    if (!$uri && $repo) {
      $uri = $ref ? $repo . '#' . $ref : $repo;
    }

    try {
      $this->artifact = Artifact::fromUri($uri);
      $this->config->set(Config::REPO, $this->artifact->getRepo());
      $this->config->set(Config::REF, $this->artifact->getRef());
    }
    catch (\RuntimeException $e) {
      throw new \RuntimeException(sprintf('Invalid repository URI: %s', $e->getMessage()), $e->getCode(), $e);
    }

    // Check if the project is a Vortex project.
    $this->config->set(Config::IS_VORTEX_PROJECT, File::contains($this->config->getDst() . DIRECTORY_SEPARATOR . 'README.md', '/badge\/Vortex-/'));

    // Flag to proceed with installation. If FALSE - the installation will only
    // print resolved values and will not proceed.
    $this->config->set(Config::PROCEED, TRUE);

    // Internal flag to enforce DEMO mode. If not set, the demo mode will be
    // discovered automatically.
    if (!is_null(Env::get(Config::IS_DEMO))) {
      $this->config->set(Config::IS_DEMO, (bool) Env::get(Config::IS_DEMO));
    }

    // Internal flag to skip processing of the demo mode.
    $this->config->set(Config::IS_DEMO_DB_DOWNLOAD_SKIP, (bool) Env::get(Config::IS_DEMO_DB_DOWNLOAD_SKIP, FALSE));

    // Set no-cleanup flag.
    $this->config->set(Config::NO_CLEANUP, (bool) $options[static::OPTION_NO_CLEANUP]);

    // Set build-now flag.
    $this->config->set(Config::BUILD_NOW, (bool) $options[static::OPTION_BUILD]);
  }

  protected function prepareDestination(): array {
    $messages = [];

    $dst = $this->config->getDst();
    if (!is_dir($dst)) {
      $dst = File::mkdir($dst);
      $messages[] = sprintf('Created directory "%s".', $dst);
    }

    if (!is_readable($dst . '/.git')) {
      $messages[] = sprintf('Initialising a new Git repository in directory "%s".', $dst);
      passthru(sprintf('git --work-tree="%s" --git-dir="%s/.git" init > /dev/null', $dst, $dst));

      if (!File::exists($dst . '/.git')) {
        throw new \RuntimeException(sprintf('Unable to initialise Git repository in directory "%s".', $dst));
      }
    }

    return $messages;
  }

  protected function copyFiles(): void {
    $src = $this->config->get(Config::TMP);
    $dst = $this->config->getDst();

    // Due to the way symlinks can be ordered, we cannot copy files one-by-one
    // into destination directory. Instead, we are removing all ignored files
    // and empty directories, making the src directory "clean", and then
    // recursively copying the whole directory.
    $all = File::scandir($src, File::ignoredPaths(), TRUE);
    $files = File::scandir($src);
    $valid_files = File::scandir($src, File::ignoredPaths());
    $dirs = array_diff($all, $valid_files);
    $ignored_files = array_diff($files, $valid_files);

    foreach ($valid_files as $valid_file) {
      $relative_file = str_replace($src . DIRECTORY_SEPARATOR, '.' . DIRECTORY_SEPARATOR, (string) $valid_file);

      if (File::isInternal($relative_file)) {
        unlink($valid_file);
      }
    }

    // Remove skipped files.
    foreach ($ignored_files as $ignored_file) {
      if (is_readable($ignored_file)) {
        unlink($ignored_file);
      }
    }

    // Remove empty directories.
    foreach ($dirs as $dir) {
      File::rmdirIfEmpty($dir);
    }

    // Src directory is now "clean" - copy it to dst directory.
    if (is_dir($src) && !File::dirIsEmpty($src)) {
      File::copy($src, $dst);
    }

    // Special case for .env.local as it may exist.
    if (!file_exists($dst . '/.env.local') && file_exists($dst . '/.env.local.example')) {
      File::copy($dst . '/.env.local.example', $dst . '/.env.local');
    }
  }

  /**
   * Prepare demo content if in demo mode.
   *
   * @return array|string
   *   Array of messages or a single message.
   */
  protected function prepareDemo(): array|string {
    if (empty($this->config->get(Config::IS_DEMO))) {
      return 'Not a demo mode.';
    }

    if (!empty($this->config->get(Config::IS_DEMO_DB_DOWNLOAD_SKIP))) {
      return sprintf('%s is set. Skipping demo database download.', Config::IS_DEMO_DB_DOWNLOAD_SKIP);
    }

    // Reload variables from destination's .env.
    Env::putFromDotenv($this->config->getDst() . '/.env');

    $url = Env::get('VORTEX_DOWNLOAD_DB_URL');
    if (empty($url)) {
      return 'No database download URL provided. Skipping demo database download.';
    }

    $data_dir = $this->config->getDst() . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_DIR', './.data');
    $db_file = Env::get('VORTEX_DB_FILE', 'db.sql');

    if (file_exists($data_dir . DIRECTORY_SEPARATOR . $db_file)) {
      return 'Database dump file already exists. Skipping demo database download.';
    }

    $messages = [];
    if (!file_exists($data_dir)) {
      $data_dir = File::mkdir($data_dir);
      $messages[] = sprintf('Created data directory "%s".', $data_dir);
    }

    $destination = $data_dir . DIRECTORY_SEPARATOR . $db_file;
    $this->getFileDownloader()->download($url, $destination);

    $messages[] = sprintf('No database dump file was found in "%s" directory.', $data_dir);
    $messages[] = sprintf('Downloaded demo database from %s.', $url);

    return $messages;
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
   * Clean up installer artifacts.
   */
  public function cleanup(): void {
    // Skip cleanup if the no-cleanup flag is set.
    if ($this->config->get(Config::NO_CLEANUP, FALSE)) {
      return;
    }

    $phar_path = \Phar::running(FALSE);
    if (!empty($phar_path) && file_exists($phar_path)) {
      @unlink($phar_path);
    }
  }

  /**
   * Get the repository downloader.
   *
   * Provides a default RepositoryDownloader instance or returns the injected
   * one. This allows tests to inject mocks via setRepositoryDownloader().
   *
   * @return \DrevOps\VortexInstaller\Downloader\RepositoryDownloader
   *   The repository downloader.
   */
  protected function getRepositoryDownloader(): RepositoryDownloader {
    return $this->repositoryDownloader ??= new RepositoryDownloader();
  }

  /**
   * Set the repository downloader.
   *
   * @param \DrevOps\VortexInstaller\Downloader\RepositoryDownloader $repositoryDownloader
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
   * @return \DrevOps\VortexInstaller\Downloader\Downloader
   *   The file downloader.
   */
  protected function getFileDownloader(): Downloader {
    return $this->fileDownloader ??= new Downloader();
  }

  /**
   * Set the file downloader.
   *
   * @param \DrevOps\VortexInstaller\Downloader\Downloader $fileDownloader
   *   The file downloader.
   */
  public function setFileDownloader(Downloader $fileDownloader): void {
    $this->fileDownloader = $fileDownloader;
  }

}
