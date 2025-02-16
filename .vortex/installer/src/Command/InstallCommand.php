<?php

declare(strict_types=1);

namespace DrevOps\Installer\Command;

use DrevOps\Installer\InstallerConfig;
use DrevOps\Installer\Prompt\Name;
use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Traits\DownloadTrait;
use DrevOps\Installer\Traits\EnvTrait;
use DrevOps\Installer\Traits\FilesystemTrait;
use DrevOps\Installer\Traits\GitTrait;
use DrevOps\Installer\Traits\PrinterTrait;
use DrevOps\Installer\Traits\PromptsTrait;
use DrevOps\Installer\Traits\TuiTrait;
use DrevOps\Installer\Utils\Callback;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use function Laravel\Prompts\progress;

/**
 * Run command.
 *
 * Install command.
 *
 * @package DrevOps\Installer\Command
 */
class InstallCommand extends Command {

  use DownloadTrait;
  use FilesystemTrait;
  use PrinterTrait;
  use TuiTrait;

  /**
   * Defines default command name.
   *
   * @var string
   */
  protected static $defaultName = 'install';

  /**
   * Defines the configuration object.
   */
  protected InstallerConfig $config;

  /**
   * Output interface.
   */
  protected OutputInterface $output;

  protected PromptManager $promptManager;

  /**
   * Constructor.
   *
   * @param string|null $name
   *   File system.
   * @param \Symfony\Component\Filesystem\Filesystem $fs
   *   Command name.
   */
  public function __construct(
    ?string $name = NULL,
    ?Filesystem $fs = NULL,
  ) {
    parent::__construct($name);
    $this->fs = is_null($fs) ? new Filesystem() : $fs;
  }

  /**
   * {@inheritdoc}
   */
  protected function configure(): void {
    $this->setName('Vortex CLI installer');
    $this->setDescription('Install Vortex CLI from remote or local repository.');
    $this->setHelp(<<<EOF
  php install destination

  php install --quiet destination

EOF
    );
    $this->addArgument('path', InputArgument::OPTIONAL, 'Destination directory. Optional. Defaults to the current directory.');

    $this->addOption('root', NULL, InputOption::VALUE_REQUIRED, 'Path to the root for file path resolution. If not specified, current directory is used.');

    $this->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'A JSON string with options.');

    $this->config = new InstallerConfig();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->output = $output;

    $this->promptManager = new PromptManager($output);

    // @see https://github.com/drevops/vortex/issues/1502
    if ($input->getOption('help') || $input->getArgument('path') == 'help') {
      $output->write($this->getHelp());

      return Command::SUCCESS;
    }

    try {
      $this->checkRequirements();

      $this->resolveOptions($input->getOptions(), $input->getArgument('path'));

      $this->doExecute();
    }
    catch (\Exception $exception) {
      $this->output->writeln([
        '<error>Installation failed with an error:</error>',
        '<error>' . $exception->getMessage() . '</error>',
      ]);

      return Command::FAILURE;
    }

    $this->printFooter();

    return Command::SUCCESS;
  }

  protected function checkRequirements(): void {
    $this->commandExists('git');
    $this->commandExists('tar');
    $this->commandExists('composer');
  }

  /**
   * Instantiate configuration from CLI option and environment variables.
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
   * @param string|null $path
   *   Destination directory. Optional. Defaults to the current directory.
   */
  protected function resolveOptions(array $options, ?string $path): void {
    if (!empty($options['quiet'])) {
      $this->config->set('quiet', TRUE);
    }

    if (!is_null($options['ansi'])) {
      $this->config->set('ANSI', $options['ansi']);
    }
    else {
      // On Windows, default to no ANSI, except in ANSICON and ConEmu.
      // Everywhere else, default to ANSI if stdout is a terminal.
      $is_ansi = (DIRECTORY_SEPARATOR === '\\')
        ? (FALSE !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI'))
        : (function_exists('posix_isatty') && posix_isatty(1));
      $this->config->set('ANSI', $is_ansi);
    }

    // Set root directory to use it for path resolution.
    $this->fsSetRootDir(!empty($options['root']) && is_scalar($options['root']) ? strval($options['root']) : NULL);

    // Set destination directory.
    if (!empty($path)) {
      $path = $this->fsGetAbsolutePath($path);

      if (file_exists($path)) {
        if (is_file($path)) {
          throw new \RuntimeException(sprintf('Destination directory "%s" is a file.', $path));
        }
      }
      else {
        $this->fs->mkdir($path);
        if (!is_readable($path) || !is_dir($path)) {
          throw new \RuntimeException(sprintf('Destination directory "%s" is not readable or does not exist.', $path));
        }
      }
    }
    $this->config->set('VORTEX_INSTALL_DST_DIR', $path ?: Env::get('VORTEX_INSTALL_DST_DIR', $this->fsGetRootDir()));

    // Load .env file from the destination directory, if it exists.
    if ($this->fs->exists($this->config->getDstDir() . '/.env')) {
      Env::loadAllValuesFromDotenv($this->config->getDstDir() . '/.env');
    }

    // Internal version of Vortex.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_VERSION', Env::get('VORTEX_VERSION', 'develop'));
    // Flag to display install debug information.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_DEBUG', (bool) Env::get('VORTEX_INSTALL_DEBUG', FALSE));
    // Flag to proceed with installation. If FALSE - the installation will only
    // print resolved values and will not proceed.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_PROCEED', (bool) Env::get('VORTEX_INSTALL_PROCEED', TRUE));
    // Temporary directory to download and expand files to.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_TMP_DIR', Env::get('VORTEX_INSTALL_TMP_DIR', File::createTempdir()));
    // Path to local Vortex repository. If not provided - remote will be used.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_LOCAL_REPO', Env::get('VORTEX_INSTALL_LOCAL_REPO'));
    // Optional commit to download. If not provided, latest release will be
    // downloaded.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_COMMIT', Env::get('VORTEX_INSTALL_COMMIT', 'HEAD'));

    // Internal flag to enforce DEMO mode. If not set, the demo mode will be
    // discovered automatically.
    if (!is_null(Env::get('VORTEX_INSTALL_DEMO'))) {
      $this->config->set('VORTEX_INSTALL_DEMO', (bool) Env::get('VORTEX_INSTALL_DEMO'));
    }
    // Internal flag to skip processing of the demo mode.
    $this->config->set('VORTEX_INSTALL_DEMO_SKIP', (bool) Env::get('VORTEX_INSTALL_DEMO_SKIP', FALSE));
  }

  protected function doExecute(): void {
    $this->printHeader();

    $this->collectAnswers();

    if (!$this->askShouldProceed()) {
      $this->printAbort();

      return;
    }

    $this->downloadScaffold();

    $this->prepareDestination();

    $this->replaceTokens();

    $this->copyFiles();

    $this->handleDemo();
  }

  protected function collectAnswers(): void {
    // Set answers that may be used in other answers' discoveries.
    $this->setAnswer('webroot', $this->discoverValue('webroot'));

    $responses = $this->promptManager->getResponses($this->config);

    die();
  }

  protected function envOrDefault($name, $default = NULL) {
    // @todo Implement this.
    return $default;
  }

  protected function downloadScaffold(): void {
    if ($this->config->get('VORTEX_INSTALL_LOCAL_REPO')) {
      $this->downloadScaffoldLocal();
    }
    else {
      $this->downloadScaffoldRemote();
    }
  }

  protected function prepareDestination(): void {
    $dst = $this->config->getDstDir();

    if (!is_dir($dst)) {
      $this->status(sprintf('Creating destination directory "%s".', $dst), self::INSTALLER_STATUS_MESSAGE, FALSE);
      mkdir($dst);

      if (!is_writable($dst)) {
        throw new \RuntimeException(sprintf('Destination directory "%s" is not writable.', $dst));
      }

      $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
    }

    if (is_readable($dst . '/.git')) {
      $this->status(sprintf('Git repository exists in "%s" - skipping initialisation.', $dst), self::INSTALLER_STATUS_MESSAGE, FALSE);
    }
    else {
      $this->status(sprintf('Initialising Git repository in directory "%s".', $dst), self::INSTALLER_STATUS_MESSAGE, FALSE);
      Callback::doExec(sprintf('git --work-tree="%s" --git-dir="%s/.git" init > /dev/null', $dst, $dst));

      if (!file_exists($dst . '/.git')) {
        throw new \RuntimeException(sprintf('Unable to init git project in directory "%s".', $dst));
      }
    }

    print ' ';
    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function replaceTokens(): void {
    $this->status('Replacing tokens ', self::INSTALLER_STATUS_MESSAGE, FALSE);

    $dir = $this->config->get('VORTEX_INSTALL_TMP_DIR');
    $this->promptManager->process($dir, fn(string $name, array $processors) => progress(
      label: 'Replacing tokens',
      steps: $processors,
    ));

    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function copyFiles(): void {
    $src = $this->config->get('VORTEX_INSTALL_TMP_DIR');
    $dst = $this->config->getDstDir();

    // Due to the way symlinks can be ordered, we cannot copy files one-by-one
    // into destination directory. Instead, we are removing all ignored files
    // and empty directories, making the src directory "clean", and then
    // recursively copying the whole directory.
    $all = File::scandirRecursive($src, File::ignorePaths(), TRUE);
    $files = File::scandirRecursive($src);
    $valid_files = File::scandirRecursive($src, File::ignorePaths());
    $dirs = array_diff($all, $valid_files);
    $ignored_files = array_diff($files, $valid_files);

    $this->status('Copying files', self::INSTALLER_STATUS_DEBUG);

    foreach ($valid_files as $filename) {
      $relative_file = str_replace($src . DIRECTORY_SEPARATOR, '.' . DIRECTORY_SEPARATOR, (string) $filename);

      if (File::isInternalPath($relative_file)) {
        $this->status(sprintf('Skipped file %s as an internal Vortex file.', $relative_file), self::INSTALLER_STATUS_DEBUG);
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
    if (empty($this->config->get('VORTEX_INSTALL_DEMO')) || !empty($this->config->get('VORTEX_INSTALL_DEMO_SKIP'))) {
      return;
    }

    // Reload variables from destination's .env.
    Env::loadAllValuesFromDotenv($this->config->getDstDir() . '/.env');

    $url = Env::get('VORTEX_DB_DOWNLOAD_CURL_URL');
    if (empty($url)) {
      return;
    }

    $data_dir = $this->config->getDstDir() . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_DIR', './.data');
    $file = Env::get('VORTEX_DB_FILE', 'db.sql');

    $this->status(sprintf('No database dump file found in "%s" directory. Downloading DEMO database from %s.', $data_dir, $url), self::INSTALLER_STATUS_MESSAGE, FALSE);

    if (!file_exists($data_dir)) {
      mkdir($data_dir);
    }

    Callback::doExec(sprintf('curl -s -L "%s" -o "%s/%s"', $url, $data_dir, $file), $output, $code);

    if ($code !== 0) {
      throw new \RuntimeException(sprintf('Unable to download demo database from "%s".', $url));
    }

    print ' ';
    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

}
