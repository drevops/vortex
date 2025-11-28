<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Command;

use DrevOps\VortexInstaller\Downloader\Downloader;
use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Runner\CommandRunner;
use DrevOps\VortexInstaller\Runner\ProcessRunner;
use DrevOps\VortexInstaller\Runner\RunnerInterface;
use DrevOps\VortexInstaller\Task\Task;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Strings;
use DrevOps\VortexInstaller\Utils\Tui;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
class InstallCommand extends Command {

  const ARG_DESTINATION = 'destination';

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
   * The command runner.
   */
  protected ?CommandRunner $runner = NULL;

  /**
   * The process runner.
   */
  protected ?ProcessRunner $processRunner = NULL;

  /**
   * The downloader.
   */
  protected ?Downloader $downloader = NULL;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('install');
    $this->setDescription('Install Vortex from remote or local repository.');
    $this->setHelp(<<<EOF
  Interactively install Vortex from the latest stable release into the current directory:
  php installer destination

  Non-interactively install Vortex from the latest stable release into the specified directory:
  php installer --no-interaction destination

  Install Vortex from the stable branch into the specified directory:
  php installer --uri=https://github.com/drevops/vortex.git@stable destination

  Install Vortex from a specific release into the specified directory:
  php installer --uri=https://github.com/drevops/vortex.git@1.2.3 destination

  Install Vortex from a specific commit into the specified directory:
  php installer --uri=https://github.com/drevops/vortex.git@abcd123 destination
EOF
    );
    $this->addArgument(static::ARG_DESTINATION, InputArgument::OPTIONAL, 'Destination directory. Optional. Defaults to the current directory.');

    $this->addOption(static::OPTION_ROOT, NULL, InputOption::VALUE_REQUIRED, 'Path to the root for file path resolution. If not specified, current directory is used.');
    $this->addOption(static::OPTION_NO_INTERACTION, 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question.');
    $this->addOption(static::OPTION_CONFIG, 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options or a path to a JSON file.');
    $this->addOption(static::OPTION_URI, 'l', InputOption::VALUE_REQUIRED, 'Remote or local repository URI with an optional git ref set after @.');
    $this->addOption(static::OPTION_NO_CLEANUP, NULL, InputOption::VALUE_NONE, 'Do not remove installer after successful installation.');
    $this->addOption(static::OPTION_BUILD, 'b', InputOption::VALUE_NONE, 'Run auto-build after installation without prompting.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    // @see https://github.com/drevops/vortex/issues/1502
    if ($input->getOption('help') || $input->getArgument('destination') == 'help') {
      $output->write($this->getHelp());

      return Command::SUCCESS;
    }

    Tui::init($output);

    try {
      $this->checkRequirements();
      $this->resolveOptions($input->getArguments(), $input->getOptions());

      Tui::init($output, !$this->config->getNoInteraction());
      $this->promptManager = new PromptManager($this->config);

      $this->header();

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
          $version = $this->getDownloader()->download($this->config->get(Config::REPO), $this->config->get(Config::REF), $this->config->get(Config::TMP));
          $this->config->set(Config::VERSION, $version);
          return $version;
        },
        hint: fn(): string => sprintf('Downloading from "%s" repository at commit "%s"', ...Downloader::parseUri($this->config->get(Config::REPO))),
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

    $this->footer();

    $should_build = $this->config->get(Config::BUILD_NOW);
    if (!$should_build && !$this->config->getNoInteraction()) {
      $should_build = Tui::confirm(
        label: 'Run the site build now?',
        default: TRUE,
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
        Tui::error('Build failed. The site was installed but build process encountered errors.');
        Tui::line('');
        Tui::line('Next steps:');
        Tui::line('  - Run: ahoy build');
        Tui::line('  - Or inspect logs for details');
        Tui::line('');

        return Command::FAILURE;
      }
    }

    // Cleanup should take place only in case of the successful installation.
    // Otherwise, the user should be able to re-run the installer.
    register_shutdown_function([$this, 'cleanup']);

    return Command::SUCCESS;
  }

  protected function checkRequirements(): void {
    $runner = $this->getProcessRunner();

    $runner->run('command -v git >/dev/null');
    if ($runner->getExitCode() !== RunnerInterface::EXIT_SUCCESS) {
      throw new \RuntimeException('Missing git.');
    }

    $runner->run('command -v curl >/dev/null');
    // @phpstan-ignore-next-line notIdentical.alwaysFalse
    if ($runner->getExitCode() !== RunnerInterface::EXIT_SUCCESS) {
      throw new \RuntimeException('Missing curl.');
    }

    $runner->run('command -v tar >/dev/null');
    // @phpstan-ignore-next-line notIdentical.alwaysFalse
    if ($runner->getExitCode() !== RunnerInterface::EXIT_SUCCESS) {
      throw new \RuntimeException('Missing tar.');
    }

    $runner->run('command -v composer >/dev/null');
    // @phpstan-ignore-next-line notIdentical.alwaysFalse
    if ($runner->getExitCode() !== RunnerInterface::EXIT_SUCCESS) {
      throw new \RuntimeException('Missing Composer.');
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
    $dst = !empty($arguments['destination']) && is_scalar($arguments[static::ARG_DESTINATION]) ? strval($arguments[static::ARG_DESTINATION]) : NULL;
    $dst = $dst ?: Env::get(Config::DST, $this->config->get(Config::DST, $this->config->get(Config::ROOT)));
    $dst = File::realpath($dst);
    $this->config->set(Config::DST, $dst, TRUE);

    // Load values from the destination .env file, if it exists.
    if (File::exists($this->config->getDst() . '/.env')) {
      Env::putFromDotenv($this->config->getDst() . '/.env');
    }

    [$repo, $ref] = Downloader::parseUri($options[static::OPTION_URI] ?: 'https://github.com/drevops/vortex.git@stable');
    $this->config->set(Config::REPO, $repo);
    $this->config->set(Config::REF, $ref);

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
    $all = File::scandirRecursive($src, File::ignoredPaths(), TRUE);
    $files = File::scandirRecursive($src);
    $valid_files = File::scandirRecursive($src, File::ignoredPaths());
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
      File::rmdirEmpty($dir);
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

    $url = Env::get('VORTEX_DB_DOWNLOAD_URL');
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

    $command = sprintf('curl -s -L "%s" -o "%s/%s"', $url, $data_dir, $db_file);
    if (passthru($command) === FALSE) {
      throw new \RuntimeException(sprintf('Unable to download demo database from %s.', $url));
    }

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

    $runner = $this->getRunner();
    $runner->run('build', args: $is_profile ? ['--profile' => '1'] : [], output: $output);

    return $runner->getExitCode() === Command::SUCCESS;
  }

  protected function header(): void {
    $logo_large = <<<EOT

██╗   ██╗  ██████╗  ██████╗  ████████╗ ███████╗ ██╗  ██╗
██║   ██║ ██╔═══██╗ ██╔══██╗ ╚══██╔══╝ ██╔════╝ ╚██╗██╔╝
██║   ██║ ██║   ██║ ██████╔╝    ██║    █████╗    ╚███╔╝
╚██╗ ██╔╝ ██║   ██║ ██╔══██╗    ██║    ██╔══╝    ██╔██╗
 ╚████╔╝  ╚██████╔╝ ██║  ██║    ██║    ███████╗ ██╔╝ ██╗
  ╚═══╝    ╚═════╝  ╚═╝  ╚═╝    ╚═╝    ╚══════╝ ╚═╝  ╚═╝

               Drupal project template

                                              by DrevOps
EOT;

    $logo_small = <<<EOT
▗▖  ▗▖ ▗▄▖ ▗▄▄▖▗▄▄▄▖▗▄▄▄▖▗▖  ▗▖
▐▌  ▐▌▐▌ ▐▌▐▌ ▐▌ █  ▐▌    ▝▚▞▘
▐▌  ▐▌▐▌ ▐▌▐▛▀▚▖ █  ▐▛▀▀▘  ▐▌
 ▝▚▞▘ ▝▚▄▞▘▐▌ ▐▌ █  ▐▙▄▄▖▗▞▘▝▚▖

   Drupal project template

                     by DrevOps
EOT;

    $max_header_width = 200;

    $logo = Tui::terminalWidth() >= 80 ? $logo_large : $logo_small;
    $logo = Tui::center($logo, Tui::terminalWidth($max_header_width), '─');
    $logo = Tui::cyan($logo);

    $version = $this->getApplication()->getVersion();
    // Depending on how the installer is run, the version may be set to
    // the placeholder value or actual version (PHAR packager will replace
    // the placeholder with the actual version).
    // We need to fence the replacement below only if the version is still set
    // to the placeholder value.
    if (str_contains($version, 'vortex-installer-version')) {
      $version = str_replace('@vortex-installer-version@', 'development', $version);
    }

    $logo .= PHP_EOL . Tui::dim(str_pad(sprintf('Installer version: %s', $version), Tui::terminalWidth($max_header_width) - 2, ' ', STR_PAD_LEFT));

    Tui::note($logo);

    $title = 'Welcome to the Vortex interactive installer';
    $content = '';

    $ref = $this->config->get(Config::REF);
    if ($ref == Downloader::REF_STABLE) {
      $content .= 'This tool will guide you through installing the latest ' . Tui::underscore('stable') . ' version of Vortex into your project.' . PHP_EOL;
    }
    elseif ($ref == Downloader::REF_HEAD) {
      $content .= 'This tool will guide you through installing the latest ' . Tui::underscore('development') . ' version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This tool will guide you through installing a ' . Tui::underscore('custom') . ' version of Vortex into your project at commit "%s".', $ref) . PHP_EOL;
    }

    $content .= PHP_EOL;

    if ($this->config->isVortexProject()) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }

    if ($this->config->getNoInteraction()) {
      $content .= 'Vortex installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
      $content .= PHP_EOL;
      $content .= 'Existing committed files may be modified. You may need to resolve some of the changes manually.' . PHP_EOL;

      $title = 'Welcome to the Vortex non-interactive installer';
    }
    else {
      $content .= 'You will be asked a few questions to tailor the configuration to your site.' . PHP_EOL;
      $content .= 'No changes will be made until you confirm everything at the end.' . PHP_EOL;
      $content .= PHP_EOL;

      if ($this->config->isVortexProject()) {
        $content .= 'If you proceed, some committed files may be modified after confirmation, and you may need to resolve some of the changes manually.' . PHP_EOL;
        $content .= PHP_EOL;
      }

      $content .= 'Press ' . Tui::yellow('Ctrl+C') . ' at any time to exit the installer.' . PHP_EOL;
      $content .= 'Press ' . Tui::yellow('Ctrl+U') . ' at any time to go back to the previous step.' . PHP_EOL;
    }

    Tui::box($content, $title);

    Tui::line(Tui::dim('Press any key to continue...'));
    Tui::getChar();
  }

  public function footer(): void {
    $output = '';
    $prefix = '  ';

    if ($this->config->isVortexProject()) {
      $title = 'Finished updating Vortex';
      $output .= 'Please review the changes and commit the required files.';
    }
    else {
      $title = 'Finished installing Vortex';
      $output .= 'Next steps:' . PHP_EOL;

      // Check for required tools and provide conditional instructions.
      $missing_tools = $this->checkRequiredTools();
      if (!empty($missing_tools)) {
        $tools_output = 'Install required tools:' . PHP_EOL;
        foreach ($missing_tools as $tool => $instructions) {
          $tools_output .= sprintf('  %s: %s', $tool, $instructions) . PHP_EOL;
        }
        $tools_output .= PHP_EOL;
        $output .= Strings::wrapLines($tools_output, $prefix);
      }

      // Allow post-install handlers to add their messages.
      $output .= Strings::wrapLines($this->promptManager->runPostInstall(), $prefix);
    }

    Tui::box($output, $title);
  }

  /**
   * Check for required development tools.
   *
   * @return array
   *   Array of missing tools with installation instructions.
   */
  protected function checkRequiredTools(): array {
    $tools = [
      'docker' => [
        'name' => 'Docker',
        'command' => 'docker',
        'instructions' => 'https://www.docker.com/get-started',
      ],
      'pygmy' => [
        'name' => 'Pygmy',
        'command' => 'pygmy',
        'instructions' => 'https://github.com/pygmystack/pygmy',
      ],
      'ahoy' => [
        'name' => 'Ahoy',
        'command' => 'ahoy',
        'instructions' => 'https://github.com/ahoy-cli/ahoy',
      ],
    ];

    $missing = [];

    foreach ($tools as $tool) {
      // Use exec with output capture to avoid output to console.
      $output = [];
      $return_code = 0;
      exec(sprintf('command -v %s 2>/dev/null', $tool['command']), $output, $return_code);

      if ($return_code !== 0) {
        $missing[$tool['name']] = $tool['instructions'];
      }
    }

    return $missing;
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
   * Get the command runner.
   *
   * Provides a default CommandRunner instance or returns the injected one.
   * This allows tests to inject mocks via setRunner().
   *
   * @return \DrevOps\VortexInstaller\Runner\CommandRunner
   *   The command runner.
   */
  protected function getRunner(): CommandRunner {
    return $this->runner ?? new CommandRunner($this->getApplication());
  }

  /**
   * Set the command runner.
   *
   * @param \DrevOps\VortexInstaller\Runner\CommandRunner $runner
   *   The command runner.
   */
  public function setRunner(CommandRunner $runner): void {
    $this->runner = $runner;
  }

  /**
   * Get the process runner.
   *
   * Provides a default ProcessRunner instance or returns the injected one.
   * This allows tests to inject mocks via setProcessRunner().
   *
   * @return \DrevOps\VortexInstaller\Runner\ProcessRunner
   *   The process runner.
   */
  protected function getProcessRunner(): ProcessRunner {
    return $this->processRunner ?? (new ProcessRunner())->disableLog()->disableStreaming();
  }

  /**
   * Set the process runner.
   *
   * @param \DrevOps\VortexInstaller\Runner\ProcessRunner $runner
   *   The process runner.
   */
  public function setProcessRunner(ProcessRunner $runner): void {
    $this->processRunner = $runner;
  }

  /**
   * Get the downloader.
   *
   * Provides a default Downloader instance or returns the injected one.
   * This allows tests to inject mocks via setDownloader().
   *
   * @return \DrevOps\VortexInstaller\Downloader\Downloader
   *   The downloader.
   */
  protected function getDownloader(): Downloader {
    return $this->downloader ?? new Downloader();
  }

  /**
   * Set the downloader.
   *
   * @param \DrevOps\VortexInstaller\Downloader\Downloader $downloader
   *   The downloader.
   */
  public function setDownloader(Downloader $downloader): void {
    $this->downloader = $downloader;
  }

}
