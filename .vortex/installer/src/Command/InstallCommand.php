<?php

declare(strict_types=1);

namespace DrevOps\Installer\Command;

use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Downloader;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;
use DrevOps\Installer\Utils\Tui;
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
 * @package DrevOps\Installer\Command
 */
class InstallCommand extends Command {

  const ARG_DESTINATION = 'destination';

  const OPTION_ROOT = 'root';

  const OPTION_NO_ITERACTION = 'no-interaction';

  const OPTION_CONFIG = 'config';

  const OPTION_QUIET = 'quiet';

  const OPTION_URI = 'uri';

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
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('Vortex CLI installer');
    $this->setDescription('Install Vortex CLI from remote or local repository.');
    $this->setHelp(<<<EOF
  Interactively install Vortex from the latest stable release into the current directory:
  php install destination

  Non-interactively install Vortex from the latest stable release into the specified directory:
  php install --no-interaction destination

  Install Vortex from a stable release into the specified directory:
  php install --uri=https://github.com/drevops/vortex.git@stable destination

  Install Vortex from a specific release into the specified directory:
  php install --uri=https://github.com/drevops/vortex.git@1.2.3 destination

  Install Vortex from a specific commit into the specified directory:
  php install --uri=https://github.com/drevops/vortex.git@abcd123 destination
EOF
    );
    $this->addArgument(static::ARG_DESTINATION, InputArgument::OPTIONAL, 'Destination directory. Optional. Defaults to the current directory.');

    $this->addOption(static::OPTION_ROOT, NULL, InputOption::VALUE_REQUIRED, 'Path to the root for file path resolution. If not specified, current directory is used.');
    $this->addOption(static::OPTION_NO_ITERACTION, 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question.');
    $this->addOption(static::OPTION_CONFIG, 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options.');
    $this->addOption(static::OPTION_URI, 'l', InputOption::VALUE_REQUIRED, 'Remote or local repository URI with an optional git ref set after @.');
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
      $pm = new PromptManager($this->config);

      static::header();

      $pm->prompt();

      Tui::list($pm->getResponsesSummary(), 'Installation summary');

      if (!$pm->shouldProceed()) {
        Tui::info('Aborting project installation. No files were changed.');

        return Command::SUCCESS;
      }

      Tui::info('Starting project installation');

      Tui::action(
        label: '⬇️ Downloading Vortex',
        hint: fn(): string => sprintf('Downloading from "%s" repository at commit "%s"', ...Downloader::parseUri($this->config->get(Config::REPO))),
        success: 'Vortex downloaded',
        action: function (): void {
          $version = (new Downloader())->download($this->config->get(Config::REPO), $this->config->get(Config::REF), $this->config->get(Config::TMP));
          $this->config->set(Config::VERSION, $version);
        },
      );

      Tui::action(
        label: '⚙️ Customizing Vortex for your project',
        success: 'Vortex was customized for your project',
        action: fn() => $pm->process(),
      );

      Tui::action(
        label: '🥣️Preparing destination directory',
        success: 'Destination directory is ready',
        action: fn(): array => $this->prepareDestination(),
      );

      Tui::action(
        label: '➡️ Copying files to the destination directory',
        success: 'Files copied to destination directory',
        action: fn() => $this->copyFiles(),
      );

      Tui::action(
        label: '🎬 Preparing demo content',
        success: 'Demo content prepared',
        action: fn(): string|array => $this->handleDemo(),
      );

      // @todo Implement the demo mode.
      // $this->handleDemo();
    }
    catch (\Exception $exception) {
      Tui::output()->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
      Tui::error('Installation failed with an error: ' . $exception->getMessage());

      return Command::FAILURE;
    }

    static::footer();

    return Command::SUCCESS;
  }

  protected function checkRequirements(): void {
    if (passthru('command -v git >/dev/null') === FALSE) {
      throw new \RuntimeException('Missing git.');
    }

    if (passthru('command -v curl >/dev/null') === FALSE) {
      throw new \RuntimeException('Missing curl.');
    }

    if (passthru('command -v tar >/dev/null') === FALSE) {
      throw new \RuntimeException('Missing tar.');
    }

    if (passthru('command -v composer >/dev/null') === FALSE) {
      throw new \RuntimeException('Missing Composer.');
    }
  }

  /**
   * Instantiate configuration from CLI options and environment variables.
   *
   * Installer configuration is a set of internal installer variables
   * prefixed with "VORTEX_INSTALL_" and used to control the installation. They
   * are read from the environment variables with $this->config->get().
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
    $config = isset($options[static::OPTION_CONFIG]) && is_scalar($options[static::OPTION_CONFIG]) ? strval($options[static::OPTION_CONFIG]) : '{}';
    $this->config = Config::fromString($config);

    $this->config->setQuiet($options[static::OPTION_QUIET]);
    $this->config->setNoInteraction($options[static::OPTION_NO_ITERACTION]);

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
    if (!is_null(Env::get(Config::IS_DEMO_MODE))) {
      $this->config->set(Config::IS_DEMO_MODE, (bool) Env::get(Config::IS_DEMO_MODE));
    }

    // Internal flag to skip processing of the demo mode.
    $this->config->set(Config::DEMO_MODE_SKIP, (bool) Env::get(Config::DEMO_MODE_SKIP, FALSE));
  }

  protected function prepareDestination(): array {
    $messages = [];

    $dst = $this->config->getDst();
    if (!is_dir($dst)) {
      File::dir($dst, TRUE);
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

    foreach ($valid_files as $filename) {
      $relative_file = str_replace($src . DIRECTORY_SEPARATOR, '.' . DIRECTORY_SEPARATOR, (string) $filename);

      if (File::isInternal($relative_file)) {
        unlink($filename);
      }
    }

    // Remove skipped files.
    foreach ($ignored_files as $skipped_file) {
      if (is_readable($skipped_file)) {
        unlink($skipped_file);
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

  protected function handleDemo(): array|string {
    if (empty($this->config->get(Config::IS_DEMO_MODE)) || !empty($this->config->get(Config::DEMO_MODE_SKIP))) {
      return ['Skipping demo database download.'];
    }

    // Reload variables from destination's .env.
    Env::putFromDotenv($this->config->getDst() . '/.env');

    $url = Env::get('VORTEX_DB_DOWNLOAD_URL');
    if (empty($url)) {
      return ['No database download URL provided. Skipping demo database download.'];
    }

    $data_dir = $this->config->getDst() . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_DIR', './.data');
    $file = Env::get('VORTEX_DB_FILE', 'db.sql');

    if (file_exists($data_dir . DIRECTORY_SEPARATOR . $file)) {
      return ['Database dump file already exists. Skipping download.'];
    }

    $messages = [];
    if (!file_exists($data_dir)) {
      File::dir($data_dir, TRUE);
      $messages[] = sprintf('Created directory "%s".', $data_dir);
    }
    $command = sprintf('curl -s -L "%s" -o "%s/%s"', $url, $data_dir, $file);

    if (passthru($command) === FALSE) {
      throw new \RuntimeException(sprintf('Unable to download demo database from "%s".', $url));
    }
    $messages[] = sprintf('No database dump file was found in "%s" directory. Downloaded DEMO database from %s.', $data_dir, $url);

    return $messages;
  }

  protected function header(): void {
    $logo = <<<EOT
-------------------------------------------------------------------------------

              ██╗   ██╗ ██████╗ ██████╗ ████████╗███████╗██╗  ██╗
              ██║   ██║██╔═══██╗██╔══██╗╚══██╔══╝██╔════╝╚██╗██╔╝
              ██║   ██║██║   ██║██████╔╝   ██║   █████╗   ╚███╔╝
              ╚██╗ ██╔╝██║   ██║██╔══██╗   ██║   ██╔══╝   ██╔██╗
               ╚████╔╝ ╚██████╔╝██║  ██║   ██║   ███████╗██╔╝ ██╗
                ╚═══╝   ╚═════╝ ╚═╝  ╚═╝   ╚═╝   ╚══════╝╚═╝  ╚═╝

                           Drupal project template

                                                                   by DrevOps
-------------------------------------------------------------------------------
EOT;

    // Print the logo only if the terminal is wide enough.
    if (Tui::terminalWidth() >= 80) {
      Tui::note(Tui::green($logo));
    }

    $title = 'Welcome to Vortex interactive installer';
    $content = '';

    $ref = $this->config->get(Config::REF);
    if ($ref == Downloader::REF_STABLE) {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    elseif ($ref == Downloader::REF_HEAD) {
      $content .= 'This will install the latest development version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $ref) . PHP_EOL;
    }

    $content .= PHP_EOL;

    if ($this->config->isVortexProject()) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }

    if ($this->config->getNoInteraction()) {
      $content .= 'Vortex installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
      $content .= PHP_EOL;
      $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;

      $title = 'Welcome to Vortex non-interactive installer';
    }
    else {
      $content .= 'Please answer the questions below to install configuration relevant to your site.' . PHP_EOL;
      $content .= 'No changes will be applied until the last confirmation step.' . PHP_EOL;
      $content .= PHP_EOL;
      $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;
      $content .= PHP_EOL;
      $content .= 'Press Ctrl+C at any time to exit this installer.' . PHP_EOL;
    }

    Tui::box($content, $title);
  }

  public function footer(): void {
    $output = '';
    if ($this->config->isVortexProject()) {
      $title = 'Finished updating Vortex 🚀🚀🚀';
      $output .= 'Please review changes and commit required files.';
    }
    else {
      $title = 'Finished installing Vortex 🚀🚀🚀';
      $output .= 'Next steps:' . PHP_EOL;
      $output .= '  cd ' . $this->config->getDst() . PHP_EOL;
      $output .= '  git add -A                       # Add all files.' . PHP_EOL;
      $output .= '  git commit -m "Initial commit."  # Commit all files.' . PHP_EOL;
      $output .= '  ahoy build                       # Build site.' . PHP_EOL;
      $output .= PHP_EOL;
      $output .= '  See https://vortex.drevops.com/quickstart';
    }

    Tui::box($output, $title);
  }

}
