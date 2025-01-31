<?php

declare(strict_types=1);

namespace DrevOps\Installer\Command;

use DrevOps\Installer\Config;
use DrevOps\Installer\File;
use DrevOps\Installer\Traits\DownloadTrait;
use DrevOps\Installer\Traits\EnvTrait;
use DrevOps\Installer\Traits\FilesystemTrait;
use DrevOps\Installer\Traits\GitTrait;
use DrevOps\Installer\Traits\PrinterTrait;
use DrevOps\Installer\Traits\PromptsTrait;
use DrevOps\Installer\Traits\TuiTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Run command.
 *
 * Install command.
 *
 * @package DrevOps\Installer\Command
 */
class InstallCommand extends Command {

  use DownloadTrait;
  use EnvTrait;
  use FilesystemTrait;
  use GitTrait;
  use PrinterTrait;
  use PromptsTrait;
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
  protected Config $config;

  /**
   * Output interface.
   */
  protected OutputInterface $output;

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

    $this->config = new Config();
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    $this->output = $output;

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
    $this->config->set('VORTEX_INSTALL_DST_DIR', $path ?: static::getenvOrDefault('VORTEX_INSTALL_DST_DIR', $this->fsGetRootDir()));

    // Load .env file from the destination directory, if it exists.
    if ($this->fs->exists($this->config->getDstDir() . '/.env')) {
      static::loadDotenv($this->config->getDstDir() . '/.env');
    }

    // Internal version of Vortex.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_VERSION', static::getenvOrDefault('VORTEX_VERSION', 'develop'));
    // Flag to display install debug information.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_DEBUG', (bool) static::getenvOrDefault('VORTEX_INSTALL_DEBUG', FALSE));
    // Flag to proceed with installation. If FALSE - the installation will only
    // print resolved values and will not proceed.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_PROCEED', (bool) static::getenvOrDefault('VORTEX_INSTALL_PROCEED', TRUE));
    // Temporary directory to download and expand files to.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_TMP_DIR', static::getenvOrDefault('VORTEX_INSTALL_TMP_DIR', File::createTempdir()));
    // Path to local Vortex repository. If not provided - remote will be used.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_LOCAL_REPO', static::getenvOrDefault('VORTEX_INSTALL_LOCAL_REPO'));
    // Optional commit to download. If not provided, latest release will be
    // downloaded.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_COMMIT', static::getenvOrDefault('VORTEX_INSTALL_COMMIT', 'HEAD'));

    // Internal flag to enforce DEMO mode. If not set, the demo mode will be
    // discovered automatically.
    if (!is_null(static::getenvOrDefault('VORTEX_INSTALL_DEMO'))) {
      $this->config->set('VORTEX_INSTALL_DEMO', (bool) static::getenvOrDefault('VORTEX_INSTALL_DEMO'));
    }
    // Internal flag to skip processing of the demo mode.
    $this->config->set('VORTEX_INSTALL_DEMO_SKIP', (bool) static::getenvOrDefault('VORTEX_INSTALL_DEMO_SKIP', FALSE));
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

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    $this->askForAnswer('name',              'What is your site name?');
    $this->askForAnswer('machine_name',      'What is your site machine name?');
    $this->askForAnswer('org',               'What is your organization name');
    $this->askForAnswer('org_machine_name',  'What is your organization machine name?');
    $this->askForAnswer('module_prefix',     'What is your project-specific module prefix?');
    $this->askForAnswer('profile',           'What is your custom profile machine name (leave empty to use "standard" profile)?');
    $this->askForAnswer('theme',             'What is your theme machine name?');
    $this->askForAnswer('domain',            'What is your site public domain?');
    $this->askForAnswer('webroot',           'Web root (web, docroot)?');

    $this->askForAnswer('provision_use_profile', 'Do you want to install from profile (leave empty or "n" for using database?');

    if ($this->getAnswer('provision_use_profile') === self::ANSWER_YES) {
      $this->setAnswer('database_download_source', 'none');
      $this->setAnswer('database_image', '');
    }
    else {
      $this->askForAnswer('database_download_source', "Where does the database dump come from into every environment:\n  - [u]rl\n  - [f]tp\n  - [a]cquia backup\n  - [l]lagoon environment\n  - [d]ocker registry?");

      if ($this->getAnswer('database_download_source') !== 'container_registry') {
        // Note that "database_store_type" is a pseudo-answer - it is only used
        // to improve UX and is not exposed as a variable (although has default,
        // discovery and normalisation callbacks).
        $this->askForAnswer('database_store_type',    '  When developing locally, do you want to import the database dump from the [f]ile or store it imported in the [d]ocker image for faster builds?');
      }

      if ($this->getAnswer('database_store_type') === 'file') {
        $this->setAnswer('database_image', '');
      }
      else {
        $this->askForAnswer('database_image',         '  What is your database image name and a tag (e.g. drevops/mariadb-drupal-data:latest)?');
      }
    }
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces

    $this->askForAnswer('override_existing_db', 'Do you want to override existing database in the environment?');

    $this->askForAnswer('ci_provider', 'Which provider do you want to use for CI ([c]ircleci, [g]ithub actions, [n]one)?');

    $this->askForAnswer('deploy_type', 'How do you deploy your code to the hosting ([w]ebhook call, [c]ode artifact, [d]ocker image, [l]agoon, [n]one as a comma-separated list)?');

    if ($this->getAnswer('database_download_source') !== 'ftp') {
      $this->askForAnswer('preserve_ftp', 'Do you want to keep FTP integration?');
    }
    else {
      $this->setAnswer('preserve_ftp', self::ANSWER_YES);
    }

    if ($this->getAnswer('database_download_source') !== 'acquia') {
      $this->askForAnswer('preserve_acquia', 'Do you want to keep Acquia Cloud integration?');
    }
    else {
      $this->setAnswer('preserve_acquia', self::ANSWER_YES);
    }

    $this->askForAnswer('preserve_lagoon', 'Do you want to keep Amazee.io Lagoon integration?');

    $this->askForAnswer('preserve_renovatebot', 'Do you want to keep RenovateBot integration?');

    $this->askForAnswer('preserve_onboarding', 'Do you want to keep onboarding checklist?');

    $this->askForAnswer('preserve_doc_comments', 'Do you want to keep detailed documentation in comments?');
    $this->askForAnswer('preserve_vortex_info', 'Do you want to keep all Vortex information?');

    $this->printSummary();

    if ($this->config->isInstallDebug()) {
      $this->printBox($this->formatValuesList($this->getAnswers(), '', $this->getTuiWidth() - 2 - 2 * 2), 'DEBUG RESOLVED ANSWERS');
    }
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
      $this->doExec(sprintf('git --work-tree="%s" --git-dir="%s/.git" init > /dev/null', $dst, $dst));

      if (!file_exists($dst . '/.git')) {
        throw new \RuntimeException(sprintf('Unable to init git project in directory "%s".', $dst));
      }
    }

    print ' ';
    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function replaceTokens(): void {
    $dir = $this->config->get('VORTEX_INSTALL_TMP_DIR');

    $this->status('Replacing tokens ', self::INSTALLER_STATUS_MESSAGE, FALSE);

    $processors = [
      'webroot',
      'profile',
      'provision_use_profile',
      'database_download_source',
      'database_image',
      'override_existing_db',
      'ci_provider',
      'deploy_type',
      'preserve_acquia',
      'preserve_lagoon',
      'preserve_ftp',
      'preserve_renovatebot',
      'preserve_onboarding',
      'string_tokens',
      'preserve_doc_comments',
      'demo_mode',
      'preserve_vortex_info',
      'vortex_internal',
      'enable_commented_code',
      'empty_lines',
    ];

    foreach ($processors as $name) {
      $this->processAnswer($name, $dir);
      $this->printTick($name);
    }

    print ' ';
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
    static::loadDotenv($this->config->getDstDir() . '/.env');

    $url = static::getenvOrDefault('VORTEX_DB_DOWNLOAD_CURL_URL');
    if (empty($url)) {
      return;
    }

    $data_dir = $this->config->getDstDir() . DIRECTORY_SEPARATOR . static::getenvOrDefault('VORTEX_DB_DIR', './.data');
    $file = static::getenvOrDefault('VORTEX_DB_FILE', 'db.sql');

    $this->status(sprintf('No database dump file found in "%s" directory. Downloading DEMO database from %s.', $data_dir, $url), self::INSTALLER_STATUS_MESSAGE, FALSE);

    if (!file_exists($data_dir)) {
      mkdir($data_dir);
    }

    $this->doExec(sprintf('curl -s -L "%s" -o "%s/%s"', $url, $data_dir, $file), $output, $code);

    if ($code !== 0) {
      throw new \RuntimeException(sprintf('Unable to download demo database from "%s".', $url));
    }

    print ' ';
    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

}
