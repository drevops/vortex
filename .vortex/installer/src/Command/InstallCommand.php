<?php

declare(strict_types=1);

namespace DrevOps\Installer\Command;

use DrevOps\Installer\Config\ConfigInterface;
use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Downloader;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;
use DrevOps\Installer\Utils\Printer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\progress;

/**
 * Run command.
 *
 * Install command.
 *
 * @package DrevOps\Installer\Command
 */
class InstallCommand extends Command {

  /**
   * Defines default command name.
   *
   * @var string
   */
  protected static $defaultName = 'install';

  /**
   * Defines the configuration object.
   */
  protected Config $config;

  /**
   * Output interface.
   */
  protected OutputInterface $output;

  protected PromptManager $promptManager;

  protected Printer $printer;

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('Vortex CLI installer');
    $this->setDescription('Install Vortex CLI from remote or local repository.');
    $this->setHelp(<<<EOF
  php install destination

  php install --no-interaction destination

EOF
    );
    $this->addArgument('destination', InputArgument::OPTIONAL, 'Destination directory. Optional. Defaults to the current directory.');

    $this->addOption('root', NULL, InputOption::VALUE_REQUIRED, 'Path to the root for file path resolution. If not specified, current directory is used.');
    $this->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question.');
    $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->output = $output;

    // @see https://github.com/drevops/vortex/issues/1502
    if ($input->getOption('help') || $input->getArgument('destination') == 'help') {
      $output->write($this->getHelp());

      return Command::SUCCESS;
    }

    try {
      $this->checkRequirements();
      $this->resolveOptions($input->getOptions(), $input->getArguments());

      $this->printer = new Printer();
      $this->printer->header($this->config);

      $this->promptManager = new PromptManager($output, $this->config);
      $this->promptManager->prompt();

      $this->printer->summary($this->config, $this->promptManager->getResponses());
      if (!$this->promptManager->shouldProceed()) {
        info('Aborting project installation. No files were changed.');

        return Command::SUCCESS;
      }

      $this->downloadVortex();

      $this->replaceTokens();

      $this->prepareDestination();
    die('RESTORE FROM HERE');

      $this->copyFiles();

      $this->handleDemo();
    }
    catch (\Exception $exception) {
      error('Installation failed with an error:' . PHP_EOL . $exception->getMessage());

      return Command::FAILURE;
    }

    $this->promptManager->printFooter();

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
      throw new \RuntimeException('Missing composer.');
    }
  }

  /**
   * Instantiate configuration from CLI options and environment variables.
   *
   * Installer configuration is a set of internal installer script variables
   * prefixed with "VORTEX_INSTALL_" and used to control the installation. They
   * are read from the environment variables with $this->config->get().
   *
   * For simplicity of naming, internal installer config variables used in
   * $this->config->get() are matching environment variables names.
   *
   * @param array<mixed> $options
   *   Array of CLI options.
   */
  protected function resolveOptions(array $options, array $arguments): void {
    $config = isset($options['config']) && is_scalar($options['config']) ? strval($options['config']) : '{}';
    $this->config = Config::fromString($config);

    $this->config->setQuiet($options['quiet']);
    $this->config->setNoInteraction($options['no-interaction']);

    // Set root directory to resolve relative paths.
    $root = !empty($options['root']) && is_scalar($options['root']) ? strval($options['root']) : File::cwd();
    $this->config->set(Config::ROOT, $root);

    // Set destination directory.
    $dst = !empty($arguments['destination']) && is_scalar($arguments['destination']) ? strval($arguments['destination']) : NULL;
    $dst = $dst ?: Env::get(Config::DST, $this->config->get(Config::DST, $this->config->get(Config::ROOT)));
    $dst = File::mkdir($dst);
    $this->config->set(Config::DST, $dst, TRUE);

    // Temporary directory to download and expand files to.
    $this->config->set(Config::TMP, File::tmpdir());

    // Load values from the destination .env file, if it exists.
    if (File::exists($this->config->getDst() . '/.env')) {
      Env::loadAllValuesFromDotenv($this->config->getDst() . '/.env');
    }

    // Check if the project is a Vortex project.
    $this->config->set(Config::IS_VORTEX_PROJECT, File::contains('/badge\/Vortex-/', $this->config->getDst() . DIRECTORY_SEPARATOR . 'README.md'));

    // Version of Vortex to download. If not provided, the latest stable
    // release will be downloaded.
    // @todo Convert to option.
    $this->config->set(Config::VERSION, 'stable');

    // Optional commit to download. If not provided, latest release will be
    // downloaded.
    // @todo Convert to option.
    $this->config->set(Config::REPO_URI, Downloader::makeUri('https://github.com/drevops/vortex.git', $this->config->get(Config::VERSION)));

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

  protected function downloadVortex(): void {
    $src = $this->config->get(Config::REPO_URI);
    $dst = $this->config->get(Config::TMP);

    [$repo, $ref] = Downloader::parseUri($src);
    note(sprintf('Downloading from "%s" repository at commit "%s" to "%s".', $repo, $ref, $dst));
    (new Downloader())->download($src, $dst);
  }

  protected function prepareDestination(): void {
    $dst = $this->config->getDst();
    File::mkdir($dst);

    if (!is_readable($dst . '/.git')) {
      note(sprintf('Initialising a new Git repository in directory "%s".', $dst));
      passthru(sprintf('git --work-tree="%s" --git-dir="%s/.git" init > /dev/null', $dst, $dst));

      if (!File::exists($dst . '/.git')) {
        throw new \RuntimeException(sprintf('Unable to initialise Git repository in directory "%s".', $dst));
      }
    }
  }

  protected function replaceTokens(): void {
    note('Replacing tokens');

    $dir = $this->config->get(Config::TMP);
    $this->promptManager->process($dir, fn(string $name, array $processors) => progress(
      label: 'Replacing tokens',
      steps: $processors,
    ));
  }

  protected function copyFiles(): void {
    $src = $this->config->get(Config::TMP);
    $dst = $this->config->getDst();

    // Due to the way symlinks can be ordered, we cannot copy files one-by-one
    // into destination directory. Instead, we are removing all ignored files
    // and empty directories, making the src directory "clean", and then
    // recursively copying the whole directory.
    $all = File::scandirRecursive($src, File::ignorePaths(), TRUE);
    $files = File::scandirRecursive($src);
    $valid_files = File::scandirRecursive($src, File::ignorePaths());
    $dirs = array_diff($all, $valid_files);
    $ignored_files = array_diff($files, $valid_files);

    // @todo Implement as a progress.
    note('Copying files');

    foreach ($valid_files as $filename) {
      $relative_file = str_replace($src . DIRECTORY_SEPARATOR, '.' . DIRECTORY_SEPARATOR, (string) $filename);

      if (File::isInternalPath($relative_file)) {
        note(sprintf('Skipped file %s as an internal Vortex file.', $relative_file), self::INSTALLER_STATUS_DEBUG);
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
      File::rmdirRecursiveEmpty($dir);
    }

    // Src directory is now "clean" - copy it to dst directory.
    if (is_dir($src) && !File::dirIsEmpty($src)) {
      File::copyRecursive($src, $dst, 0755, FALSE);
    }

    // Special case for .env.local as it may exist.
    if (!file_exists($dst . '/.env.local')) {
      File::copyRecursive($dst . '/.env.local.example', $dst . '/.env.local', 0755, FALSE);
    }
  }

  protected function handleDemo(): void {
    if (empty($this->config->get(Config::IS_DEMO_MODE)) || !empty($this->config->get(Config::DEMO_MODE_SKIP))) {
      return;
    }

    // Reload variables from destination's .env.
    Env::loadAllValuesFromDotenv($this->config->getDst() . '/.env');

    $url = Env::get('VORTEX_DB_DOWNLOAD_CURL_URL');
    if (empty($url)) {
      return;
    }

    $data_dir = $this->config->getDst() . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_DIR', './.data');
    $file = Env::get('VORTEX_DB_FILE', 'db.sql');

    note(sprintf('No database dump file found in "%s" directory. Downloading DEMO database from %s.', $data_dir, $url));

    if (!file_exists($data_dir)) {
      mkdir($data_dir);
    }

    $command = sprintf('curl -s -L "%s" -o "%s/%s"', $url, $data_dir, $file);

    if (passthru($command) === FALSE) {
      throw new \RuntimeException(sprintf('Unable to download demo database from "%s".', $url));
    }
  }

}
