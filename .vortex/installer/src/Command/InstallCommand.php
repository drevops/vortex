<?php

declare(strict_types=1);

namespace DrevOps\Installer\Command;

use DrevOps\Installer\Config;
use DrevOps\Installer\Converter;
use DrevOps\Installer\File;
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

  use EnvTrait;
  use GitTrait;
  use PrinterTrait;
  use PromptsTrait;
  use TuiTrait;
  use FilesystemTrait;

  /**
   * Defines installer status message flags.
   */
  final const INSTALLER_STATUS_SUCCESS = 0;

  final const INSTALLER_STATUS_ERROR = 1;

  final const INSTALLER_STATUS_MESSAGE = 2;

  final const INSTALLER_STATUS_DEBUG = 3;

  /**
   * Defines "yes" and "no" answer strings.
   */
  final const ANSWER_YES = 'y';

  final const ANSWER_NO = 'n';

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
   * Configures the current command.
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

    try {
      $this->checkRequirements();

      $path = $input->getArgument('path');
      $this->resolveOptions($input->getOptions(), $path);

      $this->doExecute();
    }
    catch (\Exception $exception) {
      $this->output->writeln([
        '<error>Processing failed with an error:</error>',
        '<error>' . $exception->getMessage() . '</error>',
      ]);

      return Command::FAILURE;
    }

    $this->printFooter();

    return Command::SUCCESS;
  }

  /**
   * Instantiate configuration from CLI option and environment variables.
   *
   * Installer configuration is a set of internal installer script variables,
   * read from the environment variables. These environment variables are not
   * read directly in any operations of this installer script. Instead, these
   * environment variables are accessible with $this->config->get().
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

    if (!empty($options['no-ansi'])) {
      $this->config->set('ANSI', FALSE);
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
      if (!is_readable($path) || !is_dir($path)) {
        throw new \RuntimeException(sprintf('Destination directory "%s" is not readable or does not exist.', $path));
      }
    }
    $this->config->set('VORTEX_INSTALL_DST_DIR', $path ?: static::getenvOrDefault('VORTEX_INSTALL_DST_DIR', $this->fsGetRootDir()));

    // Load .env file from the destination directory, if it exists.
    if ($this->fs->exists($this->getDstDir() . '/.env')) {
      static::loadDotenv($this->getDstDir() . '/.env');
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

  /**
   * Execute the command.
   */
  protected function doExecute(): void {
    $this->printHeader();

    $this->collectAnswers();

    if (!$this->askShouldProceed()) {
      $this->printAbort();

      return;
    }

    $this->download();

    $this->prepareDestination();

    $this->replaceTokens();

    $this->copyFiles();

    $this->processDemo();

    $this->printFooter();
  }

  protected function prepareDestination(): void {
    $dst = $this->getDstDir();

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

  /**
   * Replace tokens.
   */
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
      'string_tokens',
      'preserve_doc_comments',
      'demo_mode',
      'preserve_vortex_info',
      'vortex_internal',
      'enable_commented_code',
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
    $dst = $this->getDstDir();

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

  /**
   * Process answers.
   */
  protected function processAnswer(string $name, string $dir): mixed {
    return $this->executeCallback('process', $name, $dir);
  }

  protected function processStringTokens(string $dir): void {
    $machine_name_hyphenated = str_replace('_', '-', $this->getAnswer('machine_name'));
    $machine_name_camel_cased = Converter::toCamelCase($this->getAnswer('machine_name'), TRUE);
    $module_prefix_camel_cased = Converter::toCamelCase($this->getAnswer('module_prefix'), TRUE);
    $module_prefix_uppercase = strtoupper($module_prefix_camel_cased);
    $theme_camel_cased = Converter::toCamelCase($this->getAnswer('theme'), TRUE);
    $vortex_version_urlencoded = str_replace('-', '--', (string) $this->config->get('VORTEX_VERSION'));
    $url = $this->getAnswer('url');
    $host = parse_url($url, PHP_URL_HOST);
    $domain = $host ?: $url;
    $domain_non_www = str_starts_with((string) $domain, "www.") ? substr((string) $domain, 4) : $domain;
    $webroot = $this->getAnswer('webroot');

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    File::dirReplaceContent('your_site_theme',       $this->getAnswer('theme'),                     $dir);
    File::dirReplaceContent('YourSiteTheme',         $theme_camel_cased,                            $dir);
    File::dirReplaceContent('your_org',              $this->getAnswer('org_machine_name'),          $dir);
    File::dirReplaceContent('YOURORG',               $this->getAnswer('org'),                       $dir);
    File::dirReplaceContent('www.your-site-url.example',  $domain,                                  $dir);
    File::dirReplaceContent('your-site-url.example',      $domain_non_www,                          $dir);
    File::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('ys_search',             $this->getAnswer('module_prefix') . '_search', $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/themes/custom',  $webroot));
    File::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core',   $dir . '/scripts/custom');
    File::dirReplaceContent('ys_search',             $this->getAnswer('module_prefix') . '_search', $dir . '/scripts/custom');
    File::dirReplaceContent('YsCore',                $module_prefix_camel_cased . 'Core',           $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('YsSearch',              $module_prefix_camel_cased . 'Search',         $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('YSCODE',                $module_prefix_uppercase,                      $dir);
    File::dirReplaceContent('YSSEARCH',              $module_prefix_uppercase,                      $dir);
    File::dirReplaceContent('your-site',             $machine_name_hyphenated,                      $dir);
    File::dirReplaceContent('your_site',             $this->getAnswer('machine_name'),              $dir);
    File::dirReplaceContent('YOURSITE',              $this->getAnswer('name'),                      $dir);
    File::dirReplaceContent('YourSite',              $machine_name_camel_cased,                     $dir);

    File::replaceStringFilename('YourSiteTheme',     $theme_camel_cased,                            $dir);
    File::replaceStringFilename('your_site_theme',   $this->getAnswer('theme'),                     $dir);
    File::replaceStringFilename('YourSite',          $machine_name_camel_cased,                     $dir);
    File::replaceStringFilename('ys_core',           $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('ys_search',         $this->getAnswer('module_prefix') . '_search', $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('YsCore',            $module_prefix_camel_cased . 'Core',           $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('your_org',          $this->getAnswer('org_machine_name'),          $dir);
    File::replaceStringFilename('your_site',         $this->getAnswer('machine_name'),              $dir);

    File::dirReplaceContent('VORTEX_VERSION_URLENCODED', $vortex_version_urlencoded,                $dir);
    File::dirReplaceContent('VORTEX_VERSION',            $this->config->get('VORTEX_VERSION'),        $dir);
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
  }

  /**
   * Download Vortex source files.
   */
  protected function download(): void {
    if ($this->config->get('VORTEX_INSTALL_LOCAL_REPO')) {
      $this->downloadLocal();
    }
    else {
      $this->downloadRemote();
    }
  }

  protected function downloadLocal(): void {
    $dst = $this->config->get('VORTEX_INSTALL_TMP_DIR');
    $repo = $this->config->get('VORTEX_INSTALL_LOCAL_REPO');
    $ref = $this->config->get('VORTEX_INSTALL_COMMIT');

    $this->status(sprintf('Downloading Vortex from the local repository "%s" at ref "%s".', $repo, $ref), self::INSTALLER_STATUS_MESSAGE, FALSE);

    $command = sprintf('git --git-dir="%s/.git" --work-tree="%s" archive --format=tar "%s" | tar xf - -C "%s"', $repo, $repo, $ref, $dst);
    $this->doExec($command, $output, $code);

    $this->status(implode(PHP_EOL, $output), self::INSTALLER_STATUS_DEBUG);

    if ($code != 0) {
      throw new \RuntimeException(implode(PHP_EOL, $output));
    }

    $this->status(sprintf('Downloaded to "%s".', $dst), self::INSTALLER_STATUS_DEBUG);

    print ' ';
    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function downloadRemote(): void {
    $dst = $this->config->get('VORTEX_INSTALL_TMP_DIR');
    $org = 'drevops';
    $project = 'vortex';
    $ref = $this->config->get('VORTEX_INSTALL_COMMIT');
    $release_prefix = $this->config->get('VORTEX_VERSION');

    if ($ref == 'HEAD') {
      $release_prefix = $release_prefix == 'develop' ? NULL : $release_prefix;
      $ref = $this->findLatestVortexRelease($org, $project, $release_prefix);
      $this->config->set('VORTEX_VERSION', $ref);
    }

    $url = sprintf('https://github.com/%s/%s/archive/%s.tar.gz', $org, $project, $ref);
    $this->status(sprintf('Downloading Vortex from the remote repository "%s" at ref "%s".', $url, $ref), self::INSTALLER_STATUS_MESSAGE, FALSE);
    $this->doExec(sprintf('curl -sS -L "%s" | tar xzf - -C "%s" --strip 1', $url, $dst), $output, $code);

    if ($code != 0) {
      throw new \RuntimeException(implode(PHP_EOL, $output));
    }

    $this->status(sprintf('Downloaded to "%s".', $dst), self::INSTALLER_STATUS_DEBUG);

    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function findLatestVortexRelease(string $org, string $project, string $release_prefix): ?string {
    $release_url = sprintf('https://api.github.com/repos/%s/%s/releases', $org, $project);
    $release_contents = file_get_contents($release_url, FALSE, stream_context_create([
      'http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']],
    ]));

    if (!$release_contents) {
      throw new \RuntimeException(sprintf('Unable to download release information from "%s".', $release_url));
    }

    $records = json_decode($release_contents, TRUE);
    foreach ($records as $record) {
      if (isset($record['tag_name']) && ($release_prefix && str_contains((string) $record['tag_name'], $release_prefix) || !$release_prefix)) {
        return $record['tag_name'];
      }
    }

    return NULL;
  }

  /**
   * Gather answers.
   *
   * This is how the values pipeline works for a variable:
   * 1. Read from .env
   * 2. Read from environment
   * 3. Read from user: default->discovered->answer->normalisation->save answer
   * 4. Use answers for processing, including writing values into correct
   *    variables in .env.
   */
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
    $this->askForAnswer('url',               'What is your site public URL?');
    $this->askForAnswer('webroot',           'Web root (web, docroot)?');

    $this->askForAnswer('provision_use_profile', 'Do you want to install from profile (leave empty or "n" for using database?');

    if ($this->getAnswer('provision_use_profile') === self::ANSWER_YES) {
      $this->setAnswer('database_download_source', 'none');
      $this->setAnswer('database_image', '');
    }
    else {
      $this->askForAnswer('database_download_source', "Where does the database dump come from into every environment:\n  - [u]rl\n  - [f]tp\n  - [a]cquia backup\n  - [d]ocker registry?");

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

    $this->askForAnswer('preserve_doc_comments', 'Do you want to keep detailed documentation in comments?');
    $this->askForAnswer('preserve_vortex_info', 'Do you want to keep all Vortex information?');

    $this->printSummary();

    if ($this->isInstallDebug()) {
      $this->printBox($this->formatValuesList($this->getAnswers(), '', 80 - 6), 'DEBUG RESOLVED ANSWERS');
    }
  }

  protected function getDstDir(): ?string {
    return $this->config->get('VORTEX_INSTALL_DST_DIR');
  }

  /**
   * Shorthand to get the value of whether install should be quiet.
   */
  protected function isQuiet(): bool {
    return (bool) $this->config->get('quiet', FALSE);
  }

  /**
   * Shorthand to get the value of VORTEX_INSTALL_DEBUG.
   */
  protected function isInstallDebug(): bool {
    return (bool) $this->config->get('VORTEX_INSTALL_DEBUG', FALSE);
  }

  /**
   * Execute this class's callback.
   *
   * @param string $prefix
   *   Prefix of the callback.
   * @param string $name
   *   Name of the callback.
   *
   * @return mixed
   *   Result of the callback.
   */
  protected function executeCallback(string $prefix, string $name): mixed {
    $args = func_get_args();
    $args = array_slice($args, 2);

    $name = Converter::snakeToPascal($name);

    $callback = [static::class, $prefix . $name];
    if (method_exists($callback[0], $callback[1]) && is_callable($callback)) {
      return call_user_func_array($callback, $args);
    }

    return NULL;
  }

}
