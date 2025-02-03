<?php

declare(strict_types=1);

namespace DrevOps\Installer\Command;

use AlexSkrypnyk\Str2Name\Str2Name;
use DrevOps\Installer\Config;
use DrevOps\Installer\Converter;
use DrevOps\Installer\File;
use DrevOps\Installer\Prompt\Name;
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
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\form;
use function Laravel\Prompts\info;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

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
    $this->config->set('VORTEX_INSTALL_DST_DIR', $path ?: static::getEnvOrDefault('VORTEX_INSTALL_DST_DIR', $this->fsGetRootDir()));

    // Load .env file from the destination directory, if it exists.
    if ($this->fs->exists($this->config->getDstDir() . '/.env')) {
      static::loadDotenv($this->config->getDstDir() . '/.env');
    }

    // Internal version of Vortex.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_VERSION', static::getEnvOrDefault('VORTEX_VERSION', 'develop'));
    // Flag to display install debug information.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_DEBUG', (bool) static::getEnvOrDefault('VORTEX_INSTALL_DEBUG', FALSE));
    // Flag to proceed with installation. If FALSE - the installation will only
    // print resolved values and will not proceed.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_PROCEED', (bool) static::getEnvOrDefault('VORTEX_INSTALL_PROCEED', TRUE));
    // Temporary directory to download and expand files to.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_TMP_DIR', static::getEnvOrDefault('VORTEX_INSTALL_TMP_DIR', File::createTempdir()));
    // Path to local Vortex repository. If not provided - remote will be used.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_LOCAL_REPO', static::getEnvOrDefault('VORTEX_INSTALL_LOCAL_REPO'));
    // Optional commit to download. If not provided, latest release will be
    // downloaded.
    // @todo Convert to option and remove from the environment variables.
    $this->config->set('VORTEX_INSTALL_COMMIT', static::getEnvOrDefault('VORTEX_INSTALL_COMMIT', 'HEAD'));

    // Internal flag to enforce DEMO mode. If not set, the demo mode will be
    // discovered automatically.
    if (!is_null(static::getEnvOrDefault('VORTEX_INSTALL_DEMO'))) {
      $this->config->set('VORTEX_INSTALL_DEMO', (bool) static::getEnvOrDefault('VORTEX_INSTALL_DEMO'));
    }
    // Internal flag to skip processing of the demo mode.
    $this->config->set('VORTEX_INSTALL_DEMO_SKIP', (bool) static::getEnvOrDefault('VORTEX_INSTALL_DEMO_SKIP', FALSE));
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

    // @formatter1:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces

    $responses = form()

            ->intro('General information')

            ->add(fn($r) => text(
              label: '🔖 Site name',
              hint: 'We will use this name in the project and in the documentation.',
              placeholder: 'E.g. My Site',
              required: TRUE,
              default: Str2Name::label(static::getEnvOrDefault('VORTEX_PROJECT', basename((string) $this->config->getDstDir()))),
              transform: fn(string $v) => trim($v),
              validate: fn($v) => Str2Name::label($v) !== $v ? 'Please enter a valid name' : NULL,
            ), 'name')

            ->add(fn($r) => text(
              label: '🔖 Site machine name',
              hint: 'We will use this name for the project directory and in the code.',
              placeholder: 'E.g. my_site',
              required: TRUE,
              default: Str2Name::machine($r['name']),
              transform: fn(string $v) => trim($v),
              validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
            ), 'machine_name')

            ->add(fn($r) => text(
              label: '🏢 Organization name',
              hint: 'We will use this name in the project and in the documentation.',
              placeholder: 'E.g. My Org',
              required: TRUE,
              default: Str2Name::label($r['name']) . ' Org',
              transform: fn(string $v) => trim($v),
              validate: fn($v) => Str2Name::label($v) !== $v ? 'Please enter a valid organization name' : NULL,
            ), 'org')

            ->add(fn($r) => text(
              label: '🏢 Organization machine name',
              hint: 'We will use this name for the project directory and in the code.',
              placeholder: 'E.g. my_org',
              required: TRUE,
              default: Str2Name::machine($r['org']),
              transform: fn(string $v) => trim($v),
              validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
            ), 'org_machine_name')

            ->add(fn($r) => text(
              label: '🌐 Public domain',
              hint: 'Domain name without protocol and trailing slash.',
              placeholder: 'E.g. example.com',
              required: TRUE,
              default: 'http://'.Str2Name::kebab($r['machine_name']) . '.com',
              transform: fn(string $v) => Converter::toDomain($v),
              validate: fn($v) => filter_var($v, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === FALSE ? 'Please enter a valid domain name' : NULL,
            ), 'domain')

            ->intro('Code repository')
            ->add(fn($r) => select(
              label: '⚙️ Repository provider',
              hint: 'Vortex offers full automation with GitHub, while support for other providers is limited.',
              options: [
                'github' => 'GitHub',
                'other' => 'Other',
              ],
              default: 'github',
            ), 'code_provider')

            ->addIf(
              fn($r) => $r['code_provider'] === 'github',
              fn($r) => note("<info>We need a token to create repositories and manage webhooks.\nIt won't be saved anywhere in the file system.\nYou may skip entering the token, but then Vortex will have to skip several operations.</info>"),
              'github_token_note'
            )

            ->addIf(
              fn($r) => $r['code_provider'] === 'github',
              fn($r) => text(
                label: '🔑 GitHub personal access token (optional)',
                hint: static::getEnvOrDefault('GITHUB_TOKEN') ? 'Read from GITHUB_TOKEN environment variable.' : 'Create a new token with "repo" scopes at https://github.com/settings/tokens/new',
                placeholder: 'E.g. ghp_1234567890',
                transform: fn(string $v) => trim($v),
                validate: fn($v) => !empty($v) && !str_starts_with($v, 'ghp_') ? 'Please enter a valid token starting with "ghp_"' : NULL,
                default: static::getEnvOrDefault('GITHUB_TOKEN'),
              ), 'github_token')

              ->addIf(
                fn($r) => !empty($r['github_token']),
                fn($r) => text(
                  label: 'What is your GitHub project name?',
                  hint: 'We will use this name to create new or find an existing repository.',
                  placeholder: 'E.g. myorg/myproject',
                  transform: fn(string $v) => trim($v),
                  validate: fn(string $v) => match (TRUE) {
                    empty($v) => 'Please enter a project name',
                    !str_contains($v, '/') || (count(explode('/', $v)) !== 2 || empty(explode('/', $v)[0]) || empty(explode('/', $v)[1])) => 'Please enter a valid project name in the format "myorg/myproject"',
                    default => NULL,
                  },
                  default: $r['org_machine_name'] . '/' . $r['machine_name'],
                ), 'github_repo')

      ->intro('Drupal')

            ->add(fn($r) => confirm(
              label: 'Use a custom profile?',
              hint: 'Select "yes" to use a custom profile, or "no" to use the "standard" profile.',
              default: FALSE,
            ), 'use_custom_profile')

              ->addIf(
                fn($r) => $r['use_custom_profile'],
                fn($r) => text(
                  label: 'Custom profile machine name',
                  hint: 'Leave empty to use "standard" profile.',
                  placeholder: 'E.g. my_profile',
                  required: TRUE,
                  default: 'standard',
                  transform: fn(string $v) => trim($v),
                  validate: fn($v) => match (TRUE) {
                    !empty($v) && Converter::toAbbreviation($v) !== $v => 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.',
                    default => 'standard',
                  },
                ), 'profile')

            ->add(fn($r) => text(
              label: '🧩 Module prefix',
              hint: 'We will use this name for custom modules.',
              placeholder: 'E.g. ms (for My Site)',
              required: TRUE,
              default: Converter::toAbbreviation($r['machine_name']),
              transform: fn(string $v) => trim($v),
              validate: fn($v) => Converter::toAbbreviation($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
            ), 'module_prefix')

            ->add(fn($r) => text(
                label: '🎨 Theme machine name',
                hint: 'We will use this name for the theme directory.',
                placeholder: 'E.g. mytheme',
                required: TRUE,
                default: $r['machine_name'],
                transform: fn(string $v) => trim($v),
                validate: fn($v) => Str2Name::machine($v) !== $v ? 'Please enter a valid machine name: only lowercase letters, numbers, and underscores are allowed.' : NULL,
              ), 'theme')

            ->intro('Hosting')

            ->add(fn($r) => select(
              label: '🏠 Hosting',
              hint: 'Select the hosting provider where the project is hosted. The web root directory will be set accordingly.',
              options: [
                'acquia' => '💧 Acquia Cloud',
                'lagoon' => '🌊 Lagoon',
                'other' => '🧩 Other',
              ],
              default: NULL,
            ), 'hosting_provider')

            ->addIf(
              fn($r) => $r['hosting_provider'] !== 'other',
              fn($r) => info(sprintf('Web root will be set to "%s".', match ($r['hosting_provider']) {
                'acquia' => 'docroot',
                'lagoon' => 'web',
                default => 'web',
              })), 'webroot_note')

            ->addIf(
              fn($r) => $r['hosting_provider'] === 'other',
              fn($r) => text(
                label: 'Custom web root directory',
                hint: 'Custom directory where the web server serves the site.',
                placeholder: 'E.g. public',
                required: TRUE,
                transform: fn(string $v) => !empty(trim($v)) ? Converter::toPath($v) : trim($v),
                validate: fn($v) => empty($v) ? 'Please enter a valid directory name' : NULL,
              ), 'webroot_custom')

            ->intro('Deployment')

            ->add(function ($r) {
              $defaults = [];

              $options = [
                'artifact' => '📦 Code artifact',
                'lagoon' => '🌊 Lagoon webhook',
                'container_image' => '🐳 Container image',
                'webhook' => '🌐 Custom webhook',
              ];

              if ($r['hosting_provider'] === 'lagoon') {
                $defaults[] = 'lagoon';
              }
              if ($r['hosting_provider'] === 'acquia') {
                $defaults[] = 'artifact';
                unset($options['lagoon']);
              }

              if (empty($defaults)) {
                $defaults[] = 'webhook';
              }

              multiselect(
                label: '🚚 Deployment types',
                hint: 'You can deploy code using one or more methods.',
                options: $options,
                default: $defaults,
                required: FALSE,
              );
            }, 'deploy_type')

            ->intro('Workflow')

            ->add(fn($r) => note('<info>Provisioning</info> is the process of setting up the site in the environment with an already built codebase.'), 'provision_note')

            ->add(fn($r) => select(
              label: 'Provision type',
              hint: 'Selecting "Profile" will install site from a profile rather than a database dump.',
              options: [
                'database' => 'Database dump',
                'profile' => 'Install from profile',
              ],
              default: 'database',
            ), 'provision_type')

              ->addIf(
                fn($r) => $r['provision_type'] === 'database',
                function ($r) {
                  $options = [
                    'url' => '🌍 URL download',
                    'ftp' => '📂 FTP download',
                    'acquia' => '💧 Acquia backup',
                    'lagoon' => '🌊 Lagoon environment',
                    'container_registry' => '🐳 Container registry',
                  ];

                  if ($r['hosting_provider'] === 'acquia') {
                    unset($options['lagoon']);
                  }

                  if ($r['hosting_provider'] === 'lagoon') {
                    unset($options['acquia']);
                  }

                  select(
                    label: 'Database dump source',
                    hint: 'Database can be downloaded as a dump file or stored in a container image.',
                    options: $options,
                    default: match ($r['hosting_provider']) {
                      'acquia' => 'acquia',
                      'lagoon' => 'lagoon',
                      default => 'url',
                    },
                  );
                }, 'database_download_source')

                ->addIf(
                  fn($r) => $r['database_download_source'] === 'container_registry',
                  fn($r) => select(
                    label: 'Database store type for local development',
                    hint: 'Importing databases larger than 1GB from a file takes longer, so you can store the database in a container image for faster builds.',
                    options: [
                      'file' => 'File',
                      'container_image' => 'Container image',
                    ],
                    default: 'file',
                  ), 'database_store_type')

                    ->addIf(
                      fn($r) => $r['database_store_type'] === 'container_image',
                      fn($r) => text(
                        label: 'What is your database container image name and a tag?',
                        hint: 'Use "latest" for the latest version. CI will be building this image overnight.',
                        placeholder: 'E.g. drevops/mariadb-drupal-data:latest',
                        default: 'drevops/mariadb-drupal-data:latest',
                        transform: fn($v) => !empty(trim($v)) ? Converter::toContainerImage(trim($v)) : trim($v),
                        validate: fn($v) => empty(trim($v)) || substr_count($v, ':') > 1 ? 'Please enter a valid image name and a tag' : NULL,
                      ), 'database_store_type')

            ->intro('Continuous Integration')
            ->add(function ($r) {
              $options = [
                'gha' => 'GitHub Actions',
                'circleci' => 'CircleCI',
                'none' => 'None',
              ];

              if ($r['code_provider'] !== 'github') {
                unset($options['gha']);
              }

              select(
                label: '🔁 Continuous Integration provider',
                hint: 'Both providers support equivalent workflow.',
                options: $options,
                default: 'gha',
              );
            }, 'ci_provider')

      ->intro('Automations')
      ->add(fn($r) => select(
        label: '🔄 Dependency updates',
        hint: 'Use a self-hosted service if you can’t install a GitHub app.',
        options: [
          'renovatebot_ci' => '🤖 + 🔁 Renovate self-hosted in CI',
          'renovatebot_app' => '🤖 Renovate GitHub app',
          'none' => 'None',
        ],
        default: 'renovatebot_ci',
      ), 'dependency_updates')
      ->add(fn($r) => confirm(
        label: '👤 Auto-assign the author to their PR?',
        hint: 'Helps to keep the PRs organized.',
        default: TRUE
      ), 'assign_author_pr')
      ->add(fn($r) => confirm(
        label: '🎫 Auto-add a <info>CONFLICT</info> label to a PR when conflicts occur?',
        hint: 'Helps to keep quickly identify PRs that need attention.',
        default: TRUE
      ), 'label_merge_conflicts_pr')
      ->intro('Documentation')
      ->add(fn($r) => confirm(
        label: '📚 Preserve project documentation?',
        hint: 'Helps to maintain the project documentation within the repository.',
        default: TRUE
      ), 'preserve_project_docs')
      ->add(fn($r) => confirm(
        label: '📋 Preserve onboarding checklist?',
        hint: 'Helps to track onboarding to Vortex within the repository.',
        default: TRUE
      ), 'preserve_onboarding')
      ->add(function ($responses) {
        print_r($responses);
      })
      ->submit();

    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces

    //    if ($this->config->isInstallDebug()) {
    //      $this->printBox($this->formatValuesList($this->getAnswers(), '', $this->getTuiWidth() - 2 - 2 * 2), 'DEBUG RESOLVED ANSWERS');
    //    }

    die();
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
      'theme',
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

    $url = static::getEnvOrDefault('VORTEX_DB_DOWNLOAD_CURL_URL');
    if (empty($url)) {
      return;
    }

    $data_dir = $this->config->getDstDir() . DIRECTORY_SEPARATOR . static::getEnvOrDefault('VORTEX_DB_DIR', './.data');
    $file = static::getEnvOrDefault('VORTEX_DB_FILE', 'db.sql');

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
