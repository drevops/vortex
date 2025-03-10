<?php

declare(strict_types=1);

namespace DrevOps\Installer\Command;

use DrevOps\Installer\Prompts\Handlers\AssignAuthorPr;
use DrevOps\Installer\Prompts\Handlers\CiProvider;
use DrevOps\Installer\Prompts\Handlers\CodeProvider;
use DrevOps\Installer\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\Installer\Prompts\Handlers\DatabaseImage;
use DrevOps\Installer\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\Installer\Prompts\Handlers\DeployType;
use DrevOps\Installer\Prompts\Handlers\Domain;
use DrevOps\Installer\Prompts\Handlers\GithubRepo;
use DrevOps\Installer\Prompts\Handlers\GithubToken;
use DrevOps\Installer\Prompts\Handlers\HostingProvider;
use DrevOps\Installer\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\Installer\Prompts\Handlers\MachineName;
use DrevOps\Installer\Prompts\Handlers\ModulePrefix;
use DrevOps\Installer\Prompts\Handlers\Name;
use DrevOps\Installer\Prompts\Handlers\Org;
use DrevOps\Installer\Prompts\Handlers\OrgMachineName;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\Installer\Prompts\Handlers\PreserveDocsProject;
use DrevOps\Installer\Prompts\Handlers\Profile;
use DrevOps\Installer\Prompts\Handlers\ProvisionType;
use DrevOps\Installer\Prompts\Handlers\Theme;
use DrevOps\Installer\Prompts\Handlers\Webroot;
use DrevOps\Installer\Prompts\PromptManager;
use DrevOps\Installer\Utils\Config;
use DrevOps\Installer\Utils\Converter;
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

      Tui::init($output, !$this->config->getNoInteraction());
      $pm = new PromptManager($this->config);

      static::header();

      $pm->prompt();

      static::summary($pm->getResponses());

      if (!$pm->shouldProceed()) {
        Tui::info('Aborting project installation. No files were changed.');

        return Command::SUCCESS;
      }

      Tui::action(
        label: '⬇️ Downloading Vortex',
        hint: fn() => sprintf('Downloading from "%s" repository at commit "%s"', ...Downloader::parseUri($this->config->get(Config::REPO_URI))),
        success: fn($r) => sprintf('Vortex downloaded to "%s" directory', $r),
        action: fn() => (new Downloader())->download($this->config->get(Config::REPO_URI), $this->config->get(Config::TMP)),
      );

      Tui::action(
        label: '⚙️ Customizing Vortex for your project',
        success: 'Vortex was customized for your project',
        action: fn() => $pm->process(),
      );

      Tui::action(
        label: '🥣️Preparing destination directory',
        success: 'Destination directory is ready',
        action: fn() => $this->prepareDestination(),
      );

      Tui::action(
        label: '➡️ Copying files to destination directory',
        success: 'Files copied to destination directory',
        action: fn() => sleep(1)
      //        action: fn() => $this->copyFiles(),
      );

      die('RESTORE FROM HERE');
      $this->handleDemo();
    }
    catch (\Exception $exception) {
      Tui::error('Installation failed with an error:' . PHP_EOL . $exception->getMessage());

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
   * @param array<mixed> $options
   *   Array of CLI options.
   */
  protected function resolveOptions(array $options, array $arguments): void {
    $config = isset($options['config']) && is_scalar($options['config']) ? strval($options['config']) : '{}';
    $this->config = Config::fromString($config);

    $this->config->setQuiet($options['quiet']);
    $this->config->setNoInteraction($options['no-interaction']);

    // Set root directory to resolve relative paths.
    $root = !empty($options['root']) && is_scalar($options['root']) ? strval($options['root']) : NULL;
    if ($root) {
      $this->config->set(Config::ROOT, $root);
    }

    // Set destination directory.
    $dst = !empty($arguments['destination']) && is_scalar($arguments['destination']) ? strval($arguments['destination']) : NULL;
    $dst = $dst ?: Env::get(Config::DST, $this->config->get(Config::DST, $this->config->get(Config::ROOT)));
    $dst = File::realpath($dst);
    $this->config->set(Config::DST, $dst, TRUE);

    // Load values from the destination .env file, if it exists.
    if (File::exists($this->config->getDst() . '/.env')) {
      Env::putFromDotenv($this->config->getDst() . '/.env');
    }

    // Check if the project is a Vortex project.
    $this->config->set(Config::IS_VORTEX_PROJECT, File::contains('/badge\/Vortex-/', $this->config->getDst() . DIRECTORY_SEPARATOR . 'README.md'));

    // Version of Vortex to download. If not provided, the latest stable
    // release will be downloaded.
    // @todo Convert to option.
    $this->config->set(Config::VERSION, Downloader::VERSION_STABLE);

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

  protected function prepareDestination(): array {
    $messages = [];

    $dst = $this->config->getDst();
    if (!is_dir($dst)) {
      $messages[] = sprintf('Creating directory "%s".', $dst);
      File::mkdir($dst);
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

      if (File::isInternalPath($relative_file)) {
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
    Env::putFromDotenv($this->config->getDst() . '/.env');

    $url = Env::get('VORTEX_DB_DOWNLOAD_CURL_URL');
    if (empty($url)) {
      return;
    }

    $data_dir = $this->config->getDst() . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_DIR', './.data');
    $file = Env::get('VORTEX_DB_FILE', 'db.sql');

    Tui::note(sprintf('No database dump file found in "%s" directory. Downloading DEMO database from %s.', $data_dir, $url));

    if (!file_exists($data_dir)) {
      mkdir($data_dir);
    }

    $command = sprintf('curl -s -L "%s" -o "%s/%s"', $url, $data_dir, $file);

    if (passthru($command) === FALSE) {
      throw new \RuntimeException(sprintf('Unable to download demo database from "%s".', $url));
    }
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

    [$repo, $ref] = Downloader::parseUri($this->config->get(Config::REPO_URI));
    if ($ref == Downloader::VERSION_STABLE) {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
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

  protected function summary(array $responses): void {
    $values['General information'] = Tui::LIST_SECTION_TITLE;
    $values['🔖 Site name'] = $responses[Name::id()];
    $values['🔖 Site machine name'] = $responses[MachineName::id()];
    $values['🏢 Organization name'] = $responses[Org::id()];
    $values['🏢 Organization machine name'] = $responses[OrgMachineName::id()];
    $values['🌐 Public domain'] = $responses[Domain::id()];

    $values['Code repository'] = Tui::LIST_SECTION_TITLE;
    $values['Code provider'] = $responses[CodeProvider::id()];

    if (!empty($responses[GithubToken::id()])) {
      $values['🔑 GitHub access token'] = 'valid';
    }
    $values['GitHub repository'] = $responses[GithubRepo::id()] ?? '<empty>';

    $values['Drupal'] = Tui::LIST_SECTION_TITLE;
    $values['📁 Webroot'] = $responses[Webroot::id()];
    $values['Profile'] = $responses[Profile::id()];

    $values['🧩 Module prefix'] = $responses[ModulePrefix::id()];
    $values['🎨 Theme machine name'] = $responses[Theme::id()] ?? '<empty>';

    $values['Hosting'] = Tui::LIST_SECTION_TITLE;
    $values['🏠 Hosting provider'] = $responses[HostingProvider::id()];

    $values['Deployment'] = Tui::LIST_SECTION_TITLE;
    $values['🚚 Deployment types'] = Converter::toList($responses[DeployType::id()]);

    $values['Workflow'] = Tui::LIST_SECTION_TITLE;
    $values['Provision type'] = $responses[ProvisionType::id()];

    if ($responses[ProvisionType::id()] == ProvisionType::DATABASE) {
      $values['Database dump source'] = $responses[DatabaseDownloadSource::id()];

      if ($responses[DatabaseDownloadSource::id()] == DatabaseDownloadSource::CONTAINER_REGISTRY) {
        $values['Database container image'] = $responses[DatabaseImage::id()];
      }
    }

    $values['Continuous Integration'] = Tui::LIST_SECTION_TITLE;
    $values['♻️️CI provider'] = $responses[CiProvider::id()];

    $values['Automations'] = Tui::LIST_SECTION_TITLE;
    $values['⬆️ Dependency updates provider'] = $responses[DependencyUpdatesProvider::id()];
    $values['👤 Auto-assign PR author'] = Converter::yesNo($responses[AssignAuthorPr::id()]);
    $values['🎫 Auto-add a <info>CONFLICT</info> label to PRs'] = Converter::yesNo($responses[LabelMergeConflictsPr::id()]);

    $values['Documentation'] = Tui::LIST_SECTION_TITLE;
    $values['📚 Preserve project documentation'] = Converter::yesNo($responses[PreserveDocsProject::id()]);
    $values['📋 Preserve onboarding checklist'] = Converter::yesNo($responses[PreserveDocsOnboarding::id()]);

    $values['Locations'] = Tui::LIST_SECTION_TITLE;
    $values['Current directory'] = $this->config->getRoot();
    $values['Destination directory'] = $this->config->getDst();
    $values['Vortex repository'] = $this->config->get(Config::REPO_URI);

    Tui::list($values, 'Installation summary');
  }

  public function footer(): void {
    print PHP_EOL;

    // @todo Fix the footer.
    print 'Would print footer';
    //
    //    if ($this->isInstalled()) {
    //      $this->printBox('Finished updating Vortex. Review changes and commit required files.');
    //    }
    //    else {
    //      $this->printBox('Finished installing Vortex.');
    //
    //      $output = '';
    //      $output .= PHP_EOL;
    //      $output .= 'Next steps:' . PHP_EOL;
    //      $output .= '  cd ' . $this->config->getDst() . PHP_EOL;
    //      $output .= '  git add -A                       # Add all files.' . PHP_EOL;
    //      $output .= '  git commit -m "Initial commit."  # Commit all files.' . PHP_EOL;
    //      $output .= '  ahoy build                       # Build site.' . PHP_EOL;
    //      $output .= PHP_EOL;
    //      $output .= '  See https://vortex.drevops.com/quickstart';
    //      $this->status($output, self::INSTALLER_STATUS_SUCCESS, TRUE, FALSE);
    //    }
  }

}
