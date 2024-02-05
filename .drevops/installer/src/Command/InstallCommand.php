<?php

namespace DrevOps\Installer\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
   * Defines installer exit codes.
   */
  final const EXIT_SUCCESS = 0;

  final const EXIT_ERROR = 1;

  /**
   * Defines error level to be reported as an error.
   */
  final const ERROR_LEVEL = E_USER_WARNING;

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
   * Defines current working directory.
   *
   * @var string
   */
  protected static $currentDir;

  /**
   * {@inheritdoc}
   */
  protected static $defaultName = 'install';

  /**
   * Configures the current command.
   */
  protected function configure(): void {
    $this
      ->setName('DrevOps CLI installer')
      ->addArgument('path', InputArgument::OPTIONAL, 'Destination directory. Optional. Defaults to the current directory.')
      ->setHelp($this->printHelp());
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output): int {
    return $this->main($input, $output);
  }

  /**
   * Main functionality.
   */
  protected function main(InputInterface $input, OutputInterface $output): int {
    self::$currentDir = getcwd();

    $this->initConfig($input);

    if ($this->getConfig('help')) {
      $output->write($this->printHelp());

      return self::EXIT_SUCCESS;
    }

    $this->checkRequirements();

    $this->printHeader();

    $this->collectAnswers();

    if ($this->askShouldProceed()) {
      $this->install();

      $this->printFooter();
    }
    else {
      $this->printAbort();
    }

    return self::EXIT_SUCCESS;
  }

  protected function checkRequirements() {
    $this->commandExists('git');
    $this->commandExists('tar');
    $this->commandExists('composer');
  }

  protected function install() {
    $this->download();

    $this->prepareDestination();

    $this->replaceTokens();

    $this->copyFiles();

    $this->processDemo();
  }

  protected function prepareDestination() {
    $dst = $this->getDstDir();

    if (!is_dir($dst)) {
      $this->status(sprintf('Creating destination directory "%s".', $dst), self::INSTALLER_STATUS_MESSAGE, FALSE);
      mkdir($dst);
      if (!is_writable($dst)) {
        throw new \RuntimeException(sprintf('Destination directory "%s" is not writable.', $dst));
      }
      print ' ';
      $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
    }

    if (is_readable($dst . '/.git')) {
      $this->status(sprintf('Git repository exists in "%s" - skipping initialisation.', $dst), self::INSTALLER_STATUS_MESSAGE, FALSE);
    }
    else {
      $this->status(sprintf('Initialising Git repository in directory "%s".', $dst), self::INSTALLER_STATUS_MESSAGE, FALSE);
      $this->doExec(sprintf('git --work-tree="%s" --git-dir="%s/.git" init > /dev/null', $dst, $dst));
      if (!is_readable($dst . '/.git')) {
        throw new \RuntimeException(sprintf('Unable to init git project in directory "%s".', $dst));
      }
    }
    print ' ';
    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  /**
   * Replace tokens.
   */
  protected function replaceTokens() {
    $dir = $this->getConfig('DREVOPS_INSTALL_TMP_DIR');

    $this->status('Replacing tokens ', self::INSTALLER_STATUS_MESSAGE, FALSE);

    $processors = [
      'webroot',
      'profile',
      'provision_use_profile',
      'database_download_source',
      'database_image',
      'override_existing_db',
      'deploy_type',
      'preserve_acquia',
      'preserve_lagoon',
      'preserve_ftp',
      'preserve_renovatebot',
      'string_tokens',
      'preserve_doc_comments',
      'demo_mode',
      'preserve_drevops_info',
      'drevops_internal',
      'enable_commented_code',
    ];

    foreach ($processors as $name) {
      $this->processAnswer($name, $dir);
      $this->printTick($name);
    }

    print ' ';
    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function copyFiles() {
    $src = $this->getConfig('DREVOPS_INSTALL_TMP_DIR');
    $dst = $this->getDstDir();

    // Due to the way symlinks can be ordered, we cannot copy files one-by-one
    // into destination directory. Instead, we are removing all ignored files
    // and empty directories, making the src directory "clean", and then
    // recursively copying the whole directory.
    $all = static::scandirRecursive($src, static::ignorePaths(), TRUE);
    $files = static::scandirRecursive($src);
    $valid_files = static::scandirRecursive($src, static::ignorePaths());
    $dirs = array_diff($all, $valid_files);
    $ignored_files = array_diff($files, $valid_files);

    $this->status('Copying files', self::INSTALLER_STATUS_DEBUG);

    foreach ($valid_files as $filename) {
      $relative_file = str_replace($src . DIRECTORY_SEPARATOR, '.' . DIRECTORY_SEPARATOR, (string) $filename);

      if (static::isInternalPath($relative_file)) {
        $this->status(sprintf('Skipped file %s as an internal DrevOps file.', $relative_file), self::INSTALLER_STATUS_DEBUG);
        unlink($filename);
        continue;
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
      static::rmdirRecursiveEmpty($dir);
    }

    // Src directory is now "clean" - copy it to dst directory.
    if (is_dir($src) && !static::dirIsEmpty($src)) {
      static::copyRecursive($src, $dst, 0755, FALSE);
    }

    // Special case for .env.local as it may exist.
    if (!file_exists($dst . '/.env.local')) {
      static::copyRecursive($dst . '/.env.local.default', $dst . '/.env.local', 0755, FALSE);
    }
  }

  protected function processDemo() {
    if (empty($this->getConfig('DREVOPS_INSTALL_DEMO')) || !empty($this->getConfig('DREVOPS_INSTALL_DEMO_SKIP'))) {
      return;
    }

    // Reload variables from destination's .env.
    static::loadDotenv($this->getDstDir() . '/.env');

    $url = static::getenvOrDefault('DREVOPS_DB_DOWNLOAD_CURL_URL');
    if (empty($url)) {
      return;
    }

    $data_dir = $this->getDstDir() . DIRECTORY_SEPARATOR . static::getenvOrDefault('DREVOPS_DB_DIR', './.data');
    $file = static::getenvOrDefault('DREVOPS_DB_FILE', 'db.sql');

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

  protected static function copyRecursive($source, $dest, $permissions = 0755, $copy_empty_dirs = FALSE): bool {
    $parent = dirname((string) $dest);

    if (!is_dir($parent)) {
      mkdir($parent, $permissions, TRUE);
    }

    // Note that symlink target must exist.
    if (is_link($source)) {
      // Changing dir symlink will be relevant to the current destination's file
      // directory.
      $cur_dir = getcwd();
      chdir($parent);
      $ret = TRUE;
      if (!is_readable(basename((string) $dest))) {
        $ret = symlink(readlink($source), basename((string) $dest));
      }
      chdir($cur_dir);

      return $ret;
    }

    if (is_file($source)) {
      $ret = copy($source, $dest);
      if ($ret) {
        chmod($dest, fileperms($source));
      }

      return $ret;
    }

    if (!is_dir($dest) && $copy_empty_dirs) {
      mkdir($dest, $permissions, TRUE);
    }

    $dir = dir($source);
    while ($dir && FALSE !== $entry = $dir->read()) {
      if ($entry == '.' || $entry == '..') {
        continue;
      }
      static::copyRecursive(sprintf('%s/%s', $source, $entry), sprintf('%s/%s', $dest, $entry), $permissions, FALSE);
    }

    $dir && $dir->close();

    return TRUE;
  }

  protected function gitFileIsTracked($path, string $dir): bool {
    if (is_dir($dir . DIRECTORY_SEPARATOR . '.git')) {
      $cwd = getcwd();
      chdir($dir);
      $this->doExec(sprintf('git ls-files --error-unmatch "%s" 2>&1 >/dev/null', $path), $output, $code);
      chdir($cwd);

      return $code === 0;
    }

    return FALSE;
  }

  protected function drupalCoreProfiles(): array {
    return [
      'standard',
      'minimal',
      'testing',
      'demo_umami',
    ];
  }

  /**
   * Process answers.
   */
  protected function processAnswer($name, $dir) {
    return $this->executeCallback('process', $name, $dir);
  }

  protected function processProfile(string $dir) {
    $webroot = $this->getAnswer('webroot');
    // For core profiles - remove custom profile and direct links to it.
    if (in_array($this->getAnswer('profile'), $this->drupalCoreProfiles())) {
      static::rmdirRecursive(sprintf('%s/%s/profiles/your_site_profile', $dir, $webroot));
      static::rmdirRecursive(sprintf('%s/%s/profiles/custom/your_site_profile', $dir, $webroot));
      static::dirReplaceContent($webroot . '/profiles/your_site_profile,', '', $dir);
      static::dirReplaceContent($webroot . '/profiles/custom/your_site_profile,', '', $dir);
    }
    static::dirReplaceContent('your_site_profile', $this->getAnswer('profile'), $dir);
  }

  protected function processProvisionUseProfile(string $dir) {
    if ($this->getAnswer('provision_use_profile') == self::ANSWER_YES) {
      static::fileReplaceContent('/DREVOPS_PROVISION_USE_PROFILE=.*/', "DREVOPS_PROVISION_USE_PROFILE=1", $dir . '/.env');
      $this->removeTokenWithContent('!PROVISION_USE_PROFILE', $dir);
    }
    else {
      static::fileReplaceContent('/DREVOPS_PROVISION_USE_PROFILE=.*/', "DREVOPS_PROVISION_USE_PROFILE=0", $dir . '/.env');
      $this->removeTokenWithContent('PROVISION_USE_PROFILE', $dir);
    }
  }

  protected function processDatabaseDownloadSource(string $dir) {
    $type = $this->getAnswer('database_download_source');
    static::fileReplaceContent('/DREVOPS_DB_DOWNLOAD_SOURCE=.*/', 'DREVOPS_DB_DOWNLOAD_SOURCE=' . $type, $dir . '/.env');

    if ($type == 'docker_registry') {
      $this->removeTokenWithContent('!DREVOPS_DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY', $dir);
    }
    else {
      $this->removeTokenWithContent('DREVOPS_DB_DOWNLOAD_SOURCE_DOCKER_REGISTRY', $dir);
    }
  }

  protected function processDatabaseImage(string $dir) {
    $image = $this->getAnswer('database_image');
    static::fileReplaceContent('/DREVOPS_DB_DOCKER_IMAGE=.*/', 'DREVOPS_DB_DOCKER_IMAGE=' . $image, $dir . '/.env');

    if ($image) {
      $this->removeTokenWithContent('!DREVOPS_DB_DOCKER_IMAGE', $dir);
    }
    else {
      $this->removeTokenWithContent('DREVOPS_DB_DOCKER_IMAGE', $dir);
    }
  }

  protected function processOverrideExistingDb(string $dir) {
    if ($this->getAnswer('override_existing_db') == self::ANSWER_YES) {
      static::fileReplaceContent('/DREVOPS_PROVISION_OVERRIDE_DB=.*/', "DREVOPS_PROVISION_OVERRIDE_DB=1", $dir . '/.env');
    }
    else {
      static::fileReplaceContent('/DREVOPS_PROVISION_OVERRIDE_DB=.*/', "DREVOPS_PROVISION_OVERRIDE_DB=0", $dir . '/.env');
    }
  }

  protected function processDeployType(string $dir) {
    $type = $this->getAnswer('deploy_type');
    if ($type != 'none') {
      static::fileReplaceContent('/DREVOPS_DEPLOY_TYPES=.*/', 'DREVOPS_DEPLOY_TYPES=' . $type, $dir . '/.env');

      if (!str_contains((string) $type, 'artifact')) {
        @unlink($dir . '/.gitignore.deployment');
      }

      $this->removeTokenWithContent('!DEPLOYMENT', $dir);
    }
    else {
      @unlink($dir . '/docs/deployment.md');
      @unlink($dir . '/.gitignore.deployment');
      $this->removeTokenWithContent('DEPLOYMENT', $dir);
    }
  }

  protected function processPreserveAcquia(string $dir) {
    if ($this->getAnswer('preserve_acquia') == self::ANSWER_YES) {
      $this->removeTokenWithContent('!ACQUIA', $dir);
    }
    else {
      static::rmdirRecursive($dir . '/hooks');
      $this->removeTokenWithContent('ACQUIA', $dir);
    }
  }

  protected function processPreserveLagoon(string $dir) {
    if ($this->getAnswer('preserve_lagoon') == self::ANSWER_YES) {
      $this->removeTokenWithContent('!LAGOON', $dir);
    }
    else {
      @unlink($dir . '/drush/sites/lagoon.site.yml');
      @unlink($dir . '/.lagoon.yml');
      @unlink($dir . '/.github/workflows/dispatch-webhook-lagoon.yml');
      $this->removeTokenWithContent('LAGOON', $dir);
    }
  }

  protected function processPreserveFtp(string $dir) {
    if ($this->getAnswer('preserve_ftp') == self::ANSWER_YES) {
      $this->removeTokenWithContent('!FTP', $dir);
    }
    else {
      $this->removeTokenWithContent('FTP', $dir);
    }
  }

  protected function processPreserveRenovatebot(string $dir) {
    if ($this->getAnswer('preserve_renovatebot') == self::ANSWER_YES) {
      $this->removeTokenWithContent('!RENOVATEBOT', $dir);
    }
    else {
      @unlink($dir . '/renovate.json');
      $this->removeTokenWithContent('RENOVATEBOT', $dir);
    }
  }

  protected function processStringTokens(string $dir) {
    $machine_name_hyphenated = str_replace('_', '-', (string) $this->getAnswer('machine_name'));
    $machine_name_camel_cased = static::toCamelCase($this->getAnswer('machine_name'), TRUE);
    $module_prefix_camel_cased = static::toCamelCase($this->getAnswer('module_prefix'), TRUE);
    $module_prefix_uppercase = strtoupper((string) $module_prefix_camel_cased);
    $theme_camel_cased = static::toCamelCase($this->getAnswer('theme'), TRUE);
    $drevops_version_urlencoded = str_replace('-', '--', (string) $this->getConfig('DREVOPS_VERSION'));

    $webroot = $this->getAnswer('webroot');

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    static::dirReplaceContent('your_site_theme',       $this->getAnswer('theme'),                   $dir);
    static::dirReplaceContent('YourSiteTheme',         $theme_camel_cased,                           $dir);
    static::dirReplaceContent('your_org',              $this->getAnswer('org_machine_name'),        $dir);
    static::dirReplaceContent('YOURORG',               $this->getAnswer('org'),                     $dir);
    static::dirReplaceContent('your-site-url.example', $this->getAnswer('url'),                     $dir);
    static::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core', $dir . sprintf('/%s/modules/custom', $webroot));
    static::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core', $dir . sprintf('/%s/themes/custom', $webroot));
    static::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core', $dir . '/scripts/custom');
    static::dirReplaceContent('YsCore',                $module_prefix_camel_cased . 'Core',          $dir . sprintf('/%s/modules/custom', $webroot));
    static::dirReplaceContent('YSCODE',                $module_prefix_uppercase,                     $dir);
    static::dirReplaceContent('your-site',             $machine_name_hyphenated,                     $dir);
    static::dirReplaceContent('your_site',             $this->getAnswer('machine_name'),            $dir);
    static::dirReplaceContent('YOURSITE',              $this->getAnswer('name'),                    $dir);
    static::dirReplaceContent('YourSite',              $machine_name_camel_cased,                    $dir);

    static::replaceStringFilename('YourSiteTheme',     $theme_camel_cased,                           $dir);
    static::replaceStringFilename('your_site_theme',   $this->getAnswer('theme'),                   $dir);
    static::replaceStringFilename('YourSite',          $machine_name_camel_cased,                    $dir);
    static::replaceStringFilename('ys_core',           $this->getAnswer('module_prefix') . '_core', $dir . sprintf('/%s/modules/custom', $webroot));
    static::replaceStringFilename('YsCore',            $module_prefix_camel_cased . 'Core',          $dir . sprintf('/%s/modules/custom', $webroot));
    static::replaceStringFilename('your_org',          $this->getAnswer('org_machine_name'),        $dir);
    static::replaceStringFilename('your_site',         $this->getAnswer('machine_name'),            $dir);

    static::dirReplaceContent('DREVOPS_VERSION_URLENCODED', $drevops_version_urlencoded,             $dir);
    static::dirReplaceContent('DREVOPS_VERSION',            $this->getConfig('DREVOPS_VERSION'),   $dir);
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
  }

  protected function processPreserveDocComments(string $dir) {
    if ($this->getAnswer('preserve_doc_comments') == self::ANSWER_YES) {
      // Replace special "#: " comments with normal "#" comments.
      static::dirReplaceContent('#:', '#', $dir);
    }
    else {
      $this->removeTokenLine('#:', $dir);
    }
  }

  protected function processDemoMode(string $dir) {
    // Only discover demo mode if not explicitly set.
    if (is_null($this->getConfig('DREVOPS_INSTALL_DEMO'))) {
      if ($this->getAnswer('provision_use_profile') == self::ANSWER_NO) {
        $download_source = $this->getAnswer('database_download_source');
        $db_file = static::getenvOrDefault('DREVOPS_DB_DIR', './.data') . DIRECTORY_SEPARATOR . static::getenvOrDefault('DREVOPS_DB_FILE', 'db.sql');
        $has_comment = static::fileContains('to allow to demonstrate how DrevOps works without', $this->getDstDir() . '/.env');

        // Enable DrevOps demo mode if download source is file AND
        // there is no downloaded file present OR if there is a demo comment in
        // destination .env file.
        if ($download_source != 'docker_registry') {
          if ($has_comment || !file_exists($db_file)) {
            $this->setConfig('DREVOPS_INSTALL_DEMO', TRUE);
          }
          else {
            $this->setConfig('DREVOPS_INSTALL_DEMO', FALSE);
          }
        }
        elseif ($has_comment || $download_source == 'docker_registry') {
          $this->setConfig('DREVOPS_INSTALL_DEMO', TRUE);
        }
        else {
          $this->setConfig('DREVOPS_INSTALL_DEMO', FALSE);
        }
      }
      else {
        $this->setConfig('DREVOPS_INSTALL_DEMO', FALSE);
      }
    }

    if (!$this->getConfig('DREVOPS_INSTALL_DEMO')) {
      $this->removeTokenWithContent('DEMO', $dir);
    }
  }

  protected function processPreserveDrevopsInfo(string $dir) {
    if ($this->getAnswer('preserve_drevops_info') == self::ANSWER_NO) {
      // Remove code required for DrevOps maintenance.
      $this->removeTokenWithContent('DREVOPS_DEV', $dir);

      // Remove all other comments.
      $this->removeTokenLine('#;', $dir);
    }
  }

  protected function processDrevopsInternal(string $dir) {
    // Remove DrevOps internal files.
    static::rmdirRecursive($dir . '/.drevops/docs');
    static::rmdirRecursive($dir . '/.drevops/tests');
    static::rmdirRecursive($dir . '/scripts/drevops/utils');
    @unlink($dir . '/.github/FUNDING.yml');
    foreach (glob($dir . '/.github/workflows/drevops-*.yml') as $file) {
      @unlink($file);
    }

    // Remove other unhandled tokenized comments.
    $this->removeTokenLine('#;<', $dir);
    $this->removeTokenLine('#;>', $dir);
  }

  protected function processEnableCommentedCode(string $dir) {
    // Enable_commented_code.
    static::dirReplaceContent('##### ', '', $dir);
  }

  protected function processWebroot(string $dir) {
    $new_name = $this->getAnswer('webroot', 'web');

    if ($new_name != 'web') {
      static::dirReplaceContent('web/', $new_name . '/', $dir);
      static::dirReplaceContent('web\/', $new_name . '\/', $dir);
      static::dirReplaceContent(': web', ': ' . $new_name, $dir);
      static::dirReplaceContent('=web', '=' . $new_name, $dir);
      static::dirReplaceContent('!web', '!' . $new_name, $dir);
      static::dirReplaceContent('/\/web\//', '/' . $new_name . '/', $dir);
      rename($dir . DIRECTORY_SEPARATOR . 'web', $dir . DIRECTORY_SEPARATOR . $new_name);
    }
  }

  /**
   * Download DrevOps source files.
   */
  protected function download() {
    if ($this->getConfig('DREVOPS_INSTALL_LOCAL_REPO')) {
      $this->downloadLocal();
    }
    else {
      $this->downloadRemote();
    }
  }

  protected function downloadLocal() {
    $dst = $this->getConfig('DREVOPS_INSTALL_TMP_DIR');
    $repo = $this->getConfig('DREVOPS_INSTALL_LOCAL_REPO');
    $ref = $this->getConfig('DREVOPS_INSTALL_COMMIT');

    $this->status(sprintf('Downloading DrevOps from the local repository "%s" at ref "%s".', $repo, $ref), self::INSTALLER_STATUS_MESSAGE, FALSE);

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

  protected function downloadRemote() {
    $dst = $this->getConfig('DREVOPS_INSTALL_TMP_DIR');
    $org = 'drevops';
    $project = 'drevops';
    $ref = $this->getConfig('DREVOPS_INSTALL_COMMIT');
    $release_prefix = $this->getConfig('DREVOPS_VERSION');

    if ($ref == 'HEAD') {
      $release_prefix = $release_prefix == 'develop' ? NULL : $release_prefix;
      $ref = $this->findLatestDrevopsRelease($org, $project, $release_prefix);
      $this->setConfig('DREVOPS_VERSION', $ref);
    }

    $url = sprintf('https://github.com/%s/%s/archive/%s.tar.gz', $org, $project, $ref);
    $this->status(sprintf('Downloading DrevOps from the remote repository "%s" at ref "%s".', $url, $ref), self::INSTALLER_STATUS_MESSAGE, FALSE);
    $this->doExec(sprintf('curl -sS -L "%s" | tar xzf - -C "%s" --strip 1', $url, $dst), $output, $code);

    if ($code != 0) {
      throw new \RuntimeException(implode(PHP_EOL, $output));
    }

    $this->status(sprintf('Downloaded to "%s".', $dst), self::INSTALLER_STATUS_DEBUG);

    $this->status('Done', self::INSTALLER_STATUS_SUCCESS);
  }

  protected function findLatestDrevopsRelease($org, $project, $release_prefix) {
    $release_url = sprintf('https://api.github.com/repos/%s/%s/releases', $org, $project);
    $release_contents = file_get_contents($release_url, FALSE, stream_context_create([
      'http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']],
    ]));

    if (!$release_contents) {
      throw new \RuntimeException(sprintf('Unable to download release information from "%s".', $release_url));
    }

    $records = json_decode($release_contents, TRUE);
    foreach ($records as $record) {
      if (isset($record['tag_name']) && ($release_prefix && str_contains((string) $record['tag_name'], (string) $release_prefix) || !$release_prefix)) {
        return $record['tag_name'];
      }
    }
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
  protected function collectAnswers() {
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

    if ($this->getAnswer('provision_use_profile') == self::ANSWER_YES) {
      $this->setAnswer('database_download_source', 'none');
      $this->setAnswer('database_image', '');
    }
    else {
      $this->askForAnswer('database_download_source', "Where does the database dump come from into every environment:\n  - [u]rl\n  - [f]tp\n  - [a]cquia backup\n  - [d]ocker registry?");

      if ($this->getAnswer('database_download_source') != 'docker_registry') {
        // Note that "database_store_type" is a pseudo-answer - it is only used
        // to improve UX and is not exposed as a variable (although has default,
        // discovery and normalisation callbacks).
        $this->askForAnswer('database_store_type',    '  When developing locally, do you want to import the database dump from the [f]ile or store it imported in the [d]ocker image for faster builds?');
      }

      if ($this->getAnswer('database_store_type') == 'file') {
        $this->setAnswer('database_image', '');
      }
      else {
        $this->askForAnswer('database_image',         '  What is your database Docker image name and a tag (e.g. drevops/drevops-mariadb-drupal-data:latest)?');
      }
    }
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces

    $this->askForAnswer('override_existing_db', 'Do you want to override existing database in the environment?');

    $this->askForAnswer('deploy_type', 'How do you deploy your code to the hosting ([w]ebhook call, [c]ode artifact, [d]ocker image, [l]agoon, [n]one as a comma-separated list)?');

    if ($this->getAnswer('database_download_source') != 'ftp') {
      $this->askForAnswer('preserve_ftp', 'Do you want to keep FTP integration?');
    }
    else {
      $this->setAnswer('preserve_ftp', self::ANSWER_YES);
    }

    if ($this->getAnswer('database_download_source') != 'acquia') {
      $this->askForAnswer('preserve_acquia', 'Do you want to keep Acquia Cloud integration?');
    }
    else {
      $this->setAnswer('preserve_acquia', self::ANSWER_YES);
    }

    $this->askForAnswer('preserve_lagoon', 'Do you want to keep Amazee.io Lagoon integration?');

    $this->askForAnswer('preserve_renovatebot', 'Do you want to keep RenovateBot integration?');

    $this->askForAnswer('preserve_doc_comments', 'Do you want to keep detailed documentation in comments?');
    $this->askForAnswer('preserve_drevops_info', 'Do you want to keep all DrevOps information?');

    $this->printSummary();

    if ($this->isInstallDebug()) {
      $this->printBox($this->formatValuesList($this->getAnswers(), '', 80 - 6), 'DEBUG RESOLVED ANSWERS');
    }
  }

  protected function askShouldProceed(): bool {
    $proceed = self::ANSWER_YES;

    if (!$this->isQuiet()) {
      $proceed = $this->ask(sprintf('Proceed with installing DrevOps into your project\'s directory "%s"? (Y,n)', $this->getDstDir()), $proceed, TRUE);
    }

    // Kill-switch to not proceed with install. If false, the install will not
    // proceed despite the answer received above.
    if (!$this->getConfig('DREVOPS_INSTALL_PROCEED')) {
      $proceed = self::ANSWER_NO;
    }

    return strtolower((string) $proceed) == self::ANSWER_YES;
  }

  protected function askForAnswer($name, $question) {
    $discovered = $this->discoverValue($name);
    $answer = $this->ask($question, $discovered);
    $answer = $this->normaliseAnswer($name, $answer);

    $this->setAnswer($name, $answer);
  }

  protected function ask($question, $default, $close_handle = FALSE) {
    if ($this->isQuiet()) {
      return $default;
    }

    $question = sprintf('> %s [%s] ', $question, $default);

    $this->out($question, 'question', FALSE);
    $handle = $this->getStdinHandle();
    $answer = trim(fgets($handle));

    if ($close_handle) {
      $this->closeStdinHandle();
    }

    return empty($answer) ? $default : $answer;
  }

  /**
   * Get installer configuration.
   *
   * Installer config is a config of this installer script. For configs of the
   * project being installed, @see get_answer().
   *
   * @see init_config()
   */
  protected function getConfig($name, $default = NULL) {
    global $_config;

    return $_config[$name] ?? $default;
  }

  /**
   * Set installer configuration.
   *
   * Installer config is a config of this installer script. For configs of the
   * project being installed, @see set_answer().
   *
   * @see init_config()
   */
  protected function setConfig($name, $value) {
    global $_config;

    if (!is_null($value)) {
      $_config[$name] = $value;
    }
  }

  /**
   * Get a named option from discovered answers for the project bing installed.
   */
  protected function getAnswer($name, $default = NULL) {
    global $_answers;

    return $_answers[$name] ?? $default;
  }

  /**
   * Set a named option for discovered answers for the project bing installed.
   */
  protected function setAnswer($name, $value) {
    global $_answers;
    $_answers[$name] = $value;
  }

  /**
   * Get all options from discovered answers for the project bing installed.
   */
  protected function getAnswers() {
    global $_answers;

    return $_answers;
  }

  /**
   * Init all config.
   */
  protected function initConfig($input) {
    $this->initCliArgsAndOptions($input);

    static::loadDotenv($this->getDstDir() . '/.env');

    $this->initInstallerConfig();
  }

  /**
   * Initialise CLI options.
   */
  protected function initCliArgsAndOptions($input) {
    $arg = $input->getArguments();
    $options = $input->getOptions();

    if (!empty($options['help'])) {
      $this->setConfig('help', TRUE);
    }

    if (!empty($options['quiet'])) {
      $this->setConfig('quiet', TRUE);
    }

    if (!empty($options['no-ansi'])) {
      $this->setConfig('ANSI', FALSE);
    }
    else {
      // On Windows, default to no ANSI, except in ANSICON and ConEmu.
      // Everywhere else, default to ANSI if stdout is a terminal.
      $is_ansi = (DIRECTORY_SEPARATOR == '\\')
        ? (FALSE !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI'))
        : (function_exists('posix_isatty') && posix_isatty(1));
      $this->setConfig('ANSI', $is_ansi);
    }

    if (!empty($arg['path'])) {
      $this->setConfig('DREVOPS_INSTALL_DST_DIR', $arg['path']);
    }
    else {
      $this->setConfig('DREVOPS_INSTALL_DST_DIR', static::getenvOrDefault('DREVOPS_INSTALL_DST_DIR', self::$currentDir));
    }
  }

  /**
   * Instantiate installer configuration from environment variables.
   *
   * Installer configuration is a set of internal installer script variables,
   * read from the environment variables. These environment variables are not
   * read directly in any operations of this installer script. Instead, these
   * environment variables are accessible with get_installer_config().
   *
   * For simplicity of naming, internal installer config variables are matching
   * environment variables names.
   */
  protected function initInstallerConfig() {
    // Internal version of DrevOps.
    $this->setConfig('DREVOPS_VERSION', static::getenvOrDefault('DREVOPS_VERSION', 'develop'));
    // Flag to display install debug information.
    $this->setConfig('DREVOPS_INSTALL_DEBUG', (bool) static::getenvOrDefault('DREVOPS_INSTALL_DEBUG', FALSE));
    // Flag to proceed with installation. If FALSE - the installation will only
    // print resolved values and will not proceed.
    $this->setConfig('DREVOPS_INSTALL_PROCEED', (bool) static::getenvOrDefault('DREVOPS_INSTALL_PROCEED', TRUE));
    // Temporary directory to download and expand files to.
    $this->setConfig('DREVOPS_INSTALL_TMP_DIR', static::getenvOrDefault('DREVOPS_INSTALL_TMP_DIR', static::tempdir()));
    // Path to local DrevOps repository. If not provided - remote will be used.
    $this->setConfig('DREVOPS_INSTALL_LOCAL_REPO', static::getenvOrDefault('DREVOPS_INSTALL_LOCAL_REPO'));
    // Optional commit to download. If not provided, latest release will be
    // downloaded.
    $this->setConfig('DREVOPS_INSTALL_COMMIT', static::getenvOrDefault('DREVOPS_INSTALL_COMMIT', 'HEAD'));

    // Internal flag to enforce DEMO mode. If not set, the demo mode will be
    // discovered automatically.
    if (!is_null(static::getenvOrDefault('DREVOPS_INSTALL_DEMO'))) {
      $this->setConfig('DREVOPS_INSTALL_DEMO', (bool) static::getenvOrDefault('DREVOPS_INSTALL_DEMO'));
    }
    // Internal flag to skip processing of the demo mode.
    $this->setConfig('DREVOPS_INSTALL_DEMO_SKIP', (bool) static::getenvOrDefault('DREVOPS_INSTALL_DEMO_SKIP', FALSE));
  }

  protected function getDstDir() {
    return $this->getConfig('DREVOPS_INSTALL_DST_DIR');
  }

  /**
   * Shorthand to get the value of whether install should be quiet.
   */
  protected function isQuiet() {
    return $this->getConfig('quiet');
  }

  /**
   * Shorthand to get the value of DREVOPS_INSTALL_DEBUG.
   */
  protected function isInstallDebug() {
    return $this->getConfig('DREVOPS_INSTALL_DEBUG');
  }

  /**
   * Get default value router.
   */
  protected function getDefaultValue($name) {
    // Allow to override default values from config variables.
    $config_name = strtoupper((string) $name);

    return $this->getConfig($config_name, $this->executeCallback('getDefaultValue', $name));
  }

  protected function getDefaultValueName(): ?string {
    return static::toHumanName(static::getenvOrDefault('DREVOPS_PROJECT', basename((string) $this->getDstDir())));
  }

  protected function getDefaultValueMachineName(): string {
    return static::toMachineName($this->getAnswer('name'));
  }

  protected function getDefaultValueOrg(): string {
    return $this->getAnswer('name') . ' Org';
  }

  protected function getDefaultValueOrgMachineName(): string {
    return static::toMachineName($this->getAnswer('org'));
  }

  protected function getDefaultValueModulePrefix(): string|array {
    return $this->toAbbreviation($this->getAnswer('machine_name'));
  }

  protected function getDefaultValueProfile(): string {
    return self::ANSWER_NO;
  }

  protected function getDefaultValueTheme() {
    return $this->getAnswer('machine_name');
  }

  protected function getDefaultValueUrl(): string {
    $value = $this->getAnswer('machine_name');
    $value = str_replace('_', '-', (string) $value);

    return $value . '.com';
  }

  protected function getDefaultValueWebroot(): string {
    return 'web';
  }

  protected function getDefaultValueProvisionUseProfile(): string {
    return self::ANSWER_NO;
  }

  protected function getDefaultValueDatabaseDownloadSource(): string {
    return 'curl';
  }

  protected function getDefaultValueDatabaseStoreType(): string {
    return 'file';
  }

  protected function getDefaultValueDatabaseImage(): string {
    return 'drevops/mariadb-drupal-data:latest';
  }

  protected function getDefaultValueOverrideExistingDb(): string {
    return self::ANSWER_NO;
  }

  protected function getDefaultValueDeployType(): string {
    return 'artifact';
  }

  protected function getDefaultValuePreserveAcquia(): string {
    return self::ANSWER_NO;
  }

  protected function getDefaultValuePreserveLagoon(): string {
    return self::ANSWER_NO;
  }

  protected function getDefaultValuePreserveFtp(): string {
    return self::ANSWER_NO;
  }

  protected function getDefaultValuePreserveRenovatebot(): string {
    return self::ANSWER_YES;
  }

  protected function getDefaultValuePreserveDocComments(): string {
    return self::ANSWER_YES;
  }

  protected function getDefaultValuePreserveDrevopsInfo(): string {
    return self::ANSWER_NO;
  }

  /**
   * Discover value router.
   *
   * Value discoveries should return NULL if they don't have the resources to
   * discover a value. This means that if the value is expected to come from a
   * file but the file is not available, the function should return NULL instead
   * of a falsy value like FALSE or 0.
   */
  protected function discoverValue($name) {
    $value = $this->executeCallback('discoverValue', $name);

    return is_null($value) ? $this->getDefaultValue($name) : $value;
  }

  protected function discoverValueName(): ?string {
    $value = $this->getComposerJsonValue('description');
    if ($value && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', (string) $value, $matches) && !empty($matches[1])) {
      return $matches[1];
    }

    return NULL;
  }

  protected function discoverValueMachineName(): ?string {
    $value = $this->getComposerJsonValue('name');
    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

  protected function discoverValueOrg(): ?string {
    $value = $this->getComposerJsonValue('description');
    if ($value && preg_match('/Drupal \d+ .* of ([0-9a-zA-Z\- ]+) for ([0-9a-zA-Z\- ]+)/', (string) $value, $matches) && !empty($matches[2])) {
      return $matches[2];
    }

    return NULL;
  }

  protected function discoverValueOrgMachineName(): ?string {
    $value = $this->getComposerJsonValue('name');
    if ($value && preg_match('/([^\/]+)\/(.+)/', (string) $value, $matches) && !empty($matches[1])) {
      return $matches[1];
    }

    return NULL;
  }

  protected function discoverValueModulePrefix(): null|string|array {
    $webroot = $this->getAnswer('webroot');

    $locations = [
      $this->getDstDir() . sprintf('/%s/modules/custom/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/sites/all/modules/custom/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/*/modules/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/*/modules/custom/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/custom/*/modules/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/custom/*/modules/custom/*_core', $webroot),
    ];

    $name = $this->findMatchingPath($locations);

    if (empty($name)) {
      return NULL;
    }

    if ($name) {
      $name = basename((string) $name);
      $name = str_replace('_core', '', $name);
    }

    return $name;
  }

  protected function discoverValueProfile() {
    $webroot = $this->getAnswer('webroot');

    if ($this->isInstalled()) {
      $name = $this->getValueFromDstDotenv('DRUPAL_PROFILE');
      if (!empty($name)) {
        return $name;
      }
    }

    $locations = [
      $this->getDstDir() . sprintf('/%s/profiles/*/*.info', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/*/*.info.yml', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/custom/*/*.info', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/custom/*/*.info.yml', $webroot),
    ];

    $name = $this->findMatchingPath($locations, 'Drupal 10 profile implementation of');

    if (empty($name)) {
      return NULL;
    }

    if ($name) {
      $name = basename((string) $name);
      $name = str_replace(['.info.yml', '.info'], '', $name);
    }

    return $name;
  }

  protected function discoverValueTheme() {
    $webroot = $this->getAnswer('webroot');

    if ($this->isInstalled()) {
      $name = $this->getValueFromDstDotenv('DRUPAL_THEME');
      if (!empty($name)) {
        return $name;
      }
    }

    $locations = [
      $this->getDstDir() . sprintf('/%s/themes/custom/*/*.info', $webroot),
      $this->getDstDir() . sprintf('/%s/themes/custom/*/*.info.yml', $webroot),
      $this->getDstDir() . sprintf('/%s/sites/all/themes/custom/*/*.info', $webroot),
      $this->getDstDir() . sprintf('/%s/sites/all/themes/custom/*/*.info.yml', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/*/themes/custom/*/*.info', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/*/themes/custom/*/*.info.yml', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/custom/*/themes/custom/*/*.info', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/custom/*/themes/custom/*/*.info.yml', $webroot),
    ];

    $name = $this->findMatchingPath($locations);

    if (empty($name)) {
      return NULL;
    }

    if ($name) {
      $name = basename((string) $name);
      $name = str_replace(['.info.yml', '.info'], '', $name);
    }

    return $name;
  }

  protected function discoverValueUrl() {
    $webroot = $this->getAnswer('webroot');

    $origin = NULL;
    $path = $this->getDstDir() . sprintf('/%s/sites/default/settings.php', $webroot);

    if (!is_readable($path)) {
      return NULL;
    }

    $contents = file_get_contents($path);

    // Drupal 8 and 9.
    if (preg_match('/\$config\s*\[\'stage_file_proxy.settings\'\]\s*\[\'origin\'\]\s*=\s*[\'"]([^\'"]+)[\'"];/', $contents, $matches)) {
      if (!empty($matches[1])) {
        $origin = $matches[1];
      }
    }
    // Drupal 7.
    elseif (preg_match('/\$conf\s*\[\'stage_file_proxy_origin\'\]\s*=\s*[\'"]([^\'"]+)[\'"];/', $contents, $matches)) {
      if (!empty($matches[1])) {
        $origin = $matches[1];
      }
    }
    if ($origin) {
      $origin = parse_url($origin, PHP_URL_HOST);
    }

    return empty($origin) ? NULL : $origin;
  }

  protected function discoverValueWebroot() {
    $webroot = $this->getValueFromDstDotenv('DREVOPS_WEBROOT');

    if (empty($webroot) && $this->isInstalled()) {
      // Try from composer.json.
      $extra = $this->getComposerJsonValue('extra');
      if (!empty($extra)) {
        $webroot = $extra['drupal-scaffold']['drupal-scaffold']['locations']['web-root'] ?? NULL;
      }
    }

    return $webroot;
  }

  protected function discoverValueProvisionUseProfile(): string {
    return $this->getValueFromDstDotenv('DREVOPS_PROVISION_USE_PROFILE') ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValueDatabaseDownloadSource() {
    return $this->getValueFromDstDotenv('DREVOPS_DB_DOWNLOAD_SOURCE');
  }

  protected function discoverValueDatabaseStoreType(): string {
    return $this->discoverValueDatabaseImage() ? 'docker_image' : 'file';
  }

  protected function discoverValueDatabaseImage() {
    return $this->getValueFromDstDotenv('DREVOPS_DB_DOCKER_IMAGE');
  }

  protected function discoverValueOverrideExistingDb(): string {
    return $this->getValueFromDstDotenv('DREVOPS_PROVISION_OVERRIDE_DB') ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValueDeployType() {
    return $this->getValueFromDstDotenv('DREVOPS_DEPLOY_TYPES');
  }

  protected function discoverValuePreserveAcquia(): ?string {
    if (is_readable($this->getDstDir() . '/hooks')) {
      return self::ANSWER_YES;
    }
    $value = $this->getValueFromDstDotenv('DREVOPS_DB_DOWNLOAD_SOURCE');

    if (is_null($value)) {
      return NULL;
    }

    return $value == 'acquia' ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveLagoon(): ?string {
    if (is_readable($this->getDstDir() . '/.lagoon.yml')) {
      return self::ANSWER_YES;
    }

    if ($this->getAnswer('deploy_type') == 'lagoon') {
      return self::ANSWER_YES;
    }

    $value = $this->getValueFromDstDotenv('LAGOON_PROJECT');

    // Special case - only work with non-empty value as 'LAGOON_PROJECT'
    // may not exist in installed site's .env file.
    if (empty($value)) {
      return NULL;
    }

    return self::ANSWER_YES;
  }

  protected function discoverValuePreserveFtp(): ?string {
    $value = $this->getValueFromDstDotenv('DREVOPS_DB_DOWNLOAD_SOURCE');
    if (is_null($value)) {
      return NULL;
    }

    return $value == 'ftp' ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveRenovatebot(): ?string {
    if (!$this->isInstalled()) {
      return NULL;
    }

    return is_readable($this->getDstDir() . '/renovate.json') ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveDocComments(): ?string {
    $file = $this->getDstDir() . '/.ahoy.yml';
    if (!is_readable($file)) {
      return NULL;
    }

    return static::fileContains('Ahoy configuration file', $file) ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveDrevopsInfo(): ?string {
    $file = $this->getDstDir() . '/.ahoy.yml';
    if (!is_readable($file)) {
      return NULL;
    }

    return static::fileContains('Comments starting with', $file) ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function getValueFromDstDotenv($name, $default = NULL) {
    // Environment variables always take precedence.
    $env_value = static::getenvOrDefault($name, NULL);
    if (!is_null($env_value)) {
      return $env_value;
    }

    $file = $this->getDstDir() . '/.env';
    if (!is_readable($file)) {
      return $default;
    }
    $parsed = static::parseDotenv($file);

    return $parsed ? $parsed[$name] ?? $default : $default;
  }

  protected function findMatchingPath($paths, $text = NULL) {
    $paths = is_array($paths) ? $paths : [$paths];

    foreach ($paths as $path) {
      $files = glob($path);
      if (empty($files)) {
        continue;
      }

      if (count($files)) {
        if (!empty($text)) {
          foreach ($files as $file) {
            if (static::fileContains($text, $file)) {
              return $file;
            }
          }
        }
        else {
          return reset($files);
        }
      }
    }

    return NULL;
  }

  /**
   * Check that DrevOps is installed for this project.
   */
  protected function isInstalled(): bool {
    $path = $this->getDstDir() . DIRECTORY_SEPARATOR . 'README.md';

    return file_exists($path) && preg_match('/badge\/DrevOps\-/', file_get_contents($path));
  }

  /**
   * Normalisation router.
   */
  protected function normaliseAnswer($name, $value) {
    $normalised = $this->executeCallback('normaliseAnswer', $name, $value);

    return $normalised ?? $value;
  }

  protected function normaliseAnswerName($value): string {
    return ucfirst((string) static::toHumanName($value));
  }

  protected function normaliseAnswerMachineName($value): string {
    return static::toMachineName($value);
  }

  protected function normaliseAnswerOrgMachineName($value): string {
    return static::toMachineName($value);
  }

  protected function normaliseAnswerModulePrefix($value): string {
    return static::toMachineName($value);
  }

  protected function normaliseAnswerProfile($value): string {
    $profile = static::toMachineName($value);
    if (empty($profile) || strtolower($profile) == self::ANSWER_NO) {
      $profile = 'standard';
    }

    return $profile;
  }

  protected function normaliseAnswerTheme($value): string {
    return static::toMachineName($value);
  }

  protected function normaliseAnswerUrl($url): string|array {
    return str_replace([' ', '_'], '-', (string) $url);
  }

  protected function normaliseAnswerWebroot($value): string {
    return strtolower(trim((string) $value, '/'));
  }

  protected function normaliseAnswerProvisionUseProfile($value): string {
    return strtolower((string) $value) != self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerDatabaseDownloadSource($value): string {
    $value = strtolower((string) $value);

    return match ($value) {
      'f', 'ftp' => 'ftp',
        'a', 'acquia' => 'acquia',
        'i', 'd', 'image', 'docker', 'docker_image', 'docker_registry' => 'docker_registry',
        'c', 'curl' => 'curl',
        default => $this->getDefaultValueDatabaseDownloadSource(),
    };
  }

  protected function normaliseAnswerDatabaseStoreType($value): string {
    $value = strtolower((string) $value);

    return match ($value) {
      'i', 'd', 'image', 'docker_image', 'docker' => 'docker_image',
        'f', 'file' => 'file',
        default => $this->getDefaultValueDatabaseStoreType(),
    };
  }

  protected function normaliseAnswerDatabaseImage($value): string {
    $value = static::toMachineName($value, ['-', '/', ':', '.']);

    return str_contains($value, ':') ? $value : $value . ':latest';
  }

  protected function normaliseAnswerOverrideExistingDb($value): string {
    return strtolower((string) $value) != self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerDeployType($value): ?string {
    $types = explode(',', (string) $value);

    $normalised = [];
    foreach ($types as $type) {
      $type = trim($type);
      switch ($type) {
        case 'w':
        case 'webhook':
          $normalised[] = 'webhook';
          break;

        case 'c':
        case 'code':
        case 'a':
        case 'artifact':
          $normalised[] = 'artifact';
          break;

        case 'd':
        case 'docker':
          $normalised[] = 'docker';
          break;

        case 'l':
        case 'lagoon':
          $normalised[] = 'lagoon';
          break;

        case 'n':
        case 'none':
          $normalised[] = 'none';
          break;
      }
    }

    if (in_array('none', $normalised)) {
      return NULL;
    }

    $normalised = array_unique($normalised);

    return implode(',', $normalised);
  }

  protected function normaliseAnswerPreserveAcquia($value): string {
    return strtolower((string) $value) != self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveLagoon($value): string {
    return strtolower((string) $value) != self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveFtp($value): string {
    return strtolower((string) $value) != self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveRenovatebot($value): string {
    return strtolower((string) $value) != self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveDocComments($value): string {
    return strtolower((string) $value) != self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveDrevopsInfo($value): string {
    return strtolower((string) $value) != self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  /**
   * Print help.
   */
  protected function printHelp(): string {
    return <<<EOF
  php install destination

  php install --quiet destination

EOF;
  }

  protected function printHeader() {
    if ($this->isQuiet()) {
      $this->printHeaderQuiet();
    }
    else {
      $this->printHeaderInteractive();
    }
    print PHP_EOL;
  }

  protected function printHeaderInteractive() {
    $commit = $this->getConfig('DREVOPS_INSTALL_COMMIT');

    $content = '';
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of DrevOps into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install DrevOps into your project at commit "%s".', $commit) . PHP_EOL;
    }
    $content .= PHP_EOL;
    if ($this->isInstalled()) {
      $content .= 'It looks like DrevOps is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }
    $content .= 'Please answer the questions below to install configuration relevant to your site.' . PHP_EOL;
    $content .= 'No changes will be applied until the last confirmation step.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Press Ctrl+C at any time to exit this installer.' . PHP_EOL;

    $this->printBox($content, 'WELCOME TO DREVOPS INTERACTIVE INSTALLER');
  }

  protected function printHeaderQuiet() {
    $commit = $this->getConfig('DREVOPS_INSTALL_COMMIT');

    $content = '';
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of DrevOps into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install DrevOps into your project at commit "%s".', $commit) . PHP_EOL;
    }
    $content .= PHP_EOL;
    if ($this->isInstalled()) {
      $content .= 'It looks like DrevOps is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }
    $content .= 'DrevOps installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;

    $this->printBox($content, 'WELCOME TO DREVOPS QUIET INSTALLER');
  }

  protected function printSummary() {
    $values['Current directory'] = self::$currentDir;
    $values['Destination directory'] = $this->getDstDir();
    $values['DrevOps version'] = $this->getConfig('DREVOPS_VERSION');
    $values['DrevOps commit'] = $this->formatNotEmpty($this->getConfig('DREVOPS_INSTALL_COMMIT'), 'Latest');

    $values[] = '';
    $values[] = str_repeat('─', 80 - 2 - 2 * 2);
    $values[] = '';

    $values['Name'] = $this->getAnswer('name');
    $values['Machine name'] = $this->getAnswer('machine_name');
    $values['Organisation'] = $this->getAnswer('org');
    $values['Organisation machine name'] = $this->getAnswer('org_machine_name');
    $values['Module prefix'] = $this->getAnswer('module_prefix');
    $values['Profile'] = $this->getAnswer('profile');
    $values['Theme name'] = $this->getAnswer('theme');
    $values['URL'] = $this->getAnswer('url');
    $values['Web root'] = $this->getAnswer('webroot');

    $values['Install from profile'] = $this->formatYesNo($this->getAnswer('provision_use_profile'));

    $values['Database download source'] = $this->getAnswer('database_download_source');
    $image = $this->getAnswer('database_image');
    $values['Database store type'] = empty($image) ? 'file' : 'docker_image';
    if ($image) {
      $values['Database image name'] = $image;
    }

    $values['Override existing database'] = $this->formatYesNo($this->getAnswer('override_existing_db'));
    $values['Deployment'] = $this->formatNotEmpty($this->getAnswer('deploy_type'), 'Disabled');
    $values['FTP integration'] = $this->formatEnabled($this->getAnswer('preserve_ftp'));
    $values['Acquia integration'] = $this->formatEnabled($this->getAnswer('preserve_acquia'));
    $values['Lagoon integration'] = $this->formatEnabled($this->getAnswer('preserve_lagoon'));
    $values['RenovateBot integration'] = $this->formatEnabled($this->getAnswer('preserve_renovatebot'));
    $values['Preserve docs in comments'] = $this->formatYesNo($this->getAnswer('preserve_doc_comments'));
    $values['Preserve DrevOps comments'] = $this->formatYesNo($this->getAnswer('preserve_drevops_info'));

    $content = $this->formatValuesList($values, '', 80 - 2 - 2 * 2);

    $this->printBox($content, 'INSTALLATION SUMMARY');
  }

  protected function printAbort() {
    $this->printBox('Aborting project installation. No files were changed.');
  }

  protected function printFooter() {
    print PHP_EOL;

    if ($this->isInstalled()) {
      $this->printBox('Finished updating DrevOps. Review changes and commit required files.');
    }
    else {
      $this->printBox('Finished installing DrevOps.');

      $output = '';
      $output .= PHP_EOL;
      $output .= 'Next steps:' . PHP_EOL;
      $output .= '  cd ' . $this->getDstDir() . PHP_EOL;
      $output .= '  git add -A                       # Add all files.' . PHP_EOL;
      $output .= '  git commit -m "Initial commit."  # Commit all files.' . PHP_EOL;
      $output .= '  ahoy build                       # Build site.' . PHP_EOL;
      $output .= PHP_EOL;
      $output .= '  See https://docs.drevops.com/quickstart';
      $this->status($output, self::INSTALLER_STATUS_SUCCESS, TRUE, FALSE);
    }
  }

  protected function printTitle($text, $fill = '-', $width = 80, string $cols = '|', $has_content = FALSE) {
    $this->printDivider($fill, $width, 'down');
    $lines = explode(PHP_EOL, wordwrap((string) $text, $width - 4, PHP_EOL));
    foreach ($lines as $line) {
      $line = ' ' . $line . ' ';
      print $cols . str_pad($line, $width - 2, ' ', STR_PAD_BOTH) . $cols . PHP_EOL;
    }
    $this->printDivider($fill, $width, $has_content ? 'up' : 'both');
  }

  protected function printSubtitle($text, $fill = '=', $width = 80) {
    $is_multiline = strlen((string) $text) + 4 >= $width;
    if ($is_multiline) {
      $this->printTitle($text, $fill, $width, 'both');
    }
    else {
      $text = ' ' . $text . ' ';
      print str_pad($text, $width, $fill, STR_PAD_BOTH) . PHP_EOL;
    }
  }

  protected function printDivider($fill = '-', $width = 80, $direction = 'none') {
    $start = $fill;
    $finish = $fill;
    switch ($direction) {
      case 'up':
        $start = '╰';
        $finish = '╯';
        break;

      case 'down':
        $start = '╭';
        $finish = '╮';
        break;

      case 'both':
        $start = '├';
        $finish = '┤';
        break;
    }

    print $start . str_repeat((string) $fill, $width - 2) . $finish . PHP_EOL;
  }

  protected function printBox($content, $title = '', $fill = '─', $padding = 2, $width = 80) {
    $cols = '│';

    $max_width = $width - 2 - $padding * 2;
    $lines = explode(PHP_EOL, wordwrap(rtrim((string) $content, PHP_EOL), $max_width, PHP_EOL));
    $pad = str_pad(' ', $padding);
    $mask = sprintf('%s%s%%-%ss%s%s', $cols, $pad, $max_width, $pad, $cols) . PHP_EOL;

    print PHP_EOL;
    if (!empty($title)) {
      $this->printTitle($title, $fill, $width);
    }
    else {
      $this->printDivider($fill, $width, 'down');
    }

    array_unshift($lines, '');
    $lines[] = '';
    foreach ($lines as $line) {
      printf($mask, $line);
    }

    $this->printDivider($fill, $width, 'up');
    print PHP_EOL;
  }

  protected function printTick($text = NULL) {
    if (!empty($text) && $this->isInstallDebug()) {
      print PHP_EOL;
      $this->status($text, self::INSTALLER_STATUS_DEBUG, FALSE);
    }
    else {
      $this->status('.', self::INSTALLER_STATUS_MESSAGE, FALSE, FALSE);
    }
  }

  protected function formatValuesList($values, $delim = '', $width = 80): string {
    // Line width - length of delimiters * 2 - 2 spacers.
    $line_width = $width - strlen((string) $delim) * 2 - 2;

    // Max name length + spaced on the sides + colon.
    $max_name_width = max(array_map('strlen', array_keys($values))) + 2 + 1;

    // Whole width - (name width + 2 delimiters on the sides + 1 delimiter in
    // the middle + 2 spaces on the sides  + 2 spaces for the center delimiter).
    $value_width = $width - ($max_name_width + strlen((string) $delim) * 2 + strlen((string) $delim) + 2 + 2);

    $mask1 = sprintf('%s %%%ds %s %%-%s.%ss %s', $delim, $max_name_width, $delim, $value_width, $value_width, $delim) . PHP_EOL;
    $mask2 = sprintf('%s%%2$%ss%s', $delim, $line_width, $delim) . PHP_EOL;

    $output = [];
    foreach ($values as $name => $value) {
      $is_multiline_value = strlen((string) $value) > $value_width;

      if (is_numeric($name)) {
        $name = '';
        $mask = $mask2;
        $is_multiline_value = FALSE;
      }
      else {
        $name .= ':';
        $mask = $mask1;
      }

      if ($is_multiline_value) {
        $lines = array_filter(explode(PHP_EOL, chunk_split((string) $value, $value_width, PHP_EOL)));
        $first_line = array_shift($lines);
        $output[] = sprintf($mask, $name, $first_line);
        foreach ($lines as $line) {
          $output[] = sprintf($mask, '', $line);
        }
      }
      else {
        $output[] = sprintf($mask, $name, $value);
      }
    }

    return implode('', $output);
  }

  protected function formatEnabled($value): string {
    return $value && strtolower((string) $value) != 'n' ? 'Enabled' : 'Disabled';
  }

  protected function formatYesNo($value): string {
    return $value == self::ANSWER_YES ? 'Yes' : 'No';
  }

  protected function formatNotEmpty($value, $default) {
    return empty($value) ? $default : $value;
  }

  public static function fileContains($needle, $file): int|bool {
    if (!is_readable($file)) {
      return FALSE;
    }

    $content = file_get_contents($file);

    if (static::isRegex($needle)) {
      return preg_match($needle, $content);
    }

    return str_contains($content, (string) $needle);
  }

  protected static function dirContains($needle, string $dir): bool {
    $files = static::scandirRecursive($dir, static::ignorePaths());
    foreach ($files as $filename) {
      if (static::fileContains($needle, $filename)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  protected static function isRegex($str): bool {
    if ($str === '' || strlen((string) $str) < 3) {
      return FALSE;
    }

    return @preg_match($str, '') !== FALSE;
  }

  protected static function fileReplaceContent($needle, $replacement, $filename) {
    if (!is_readable($filename) || static::fileIsExcludedFromProcessing($filename)) {
      return FALSE;
    }

    $content = file_get_contents($filename);

    if (static::isRegex($needle)) {
      $replaced = preg_replace($needle, (string) $replacement, $content);
    }
    else {
      $replaced = str_replace($needle, $replacement, $content);
    }
    if ($replaced != $content) {
      file_put_contents($filename, $replaced);
    }
  }

  protected static function dirReplaceContent($needle, $replacement, string $dir) {
    $files = static::scandirRecursive($dir, static::ignorePaths());
    foreach ($files as $filename) {
      static::fileReplaceContent($needle, $replacement, $filename);
    }
  }

  protected function removeTokenWithContent(string $token, string $dir) {
    $files = static::scandirRecursive($dir, static::ignorePaths());
    foreach ($files as $filename) {
      static::removeTokenFromFile($filename, '#;< ' . $token, '#;> ' . $token, TRUE);
    }
  }

  protected function removeTokenLine($token, string $dir) {
    if (!empty($token)) {
      $files = static::scandirRecursive($dir, static::ignorePaths());
      foreach ($files as $filename) {
        static::removeTokenFromFile($filename, $token, NULL);
      }
    }
  }

  public static function removeTokenFromFile($filename, $token_begin, $token_end = NULL, $with_content = FALSE): void {
    if (self::fileIsExcludedFromProcessing($filename)) {
      return;
    }

    $token_end = $token_end ?? $token_begin;

    $content = file_get_contents($filename);

    if ($token_begin != $token_end) {
      $token_begin_count = preg_match_all('/' . preg_quote((string) $token_begin) . '/', $content);
      $token_end_count = preg_match_all('/' . preg_quote((string) $token_end) . '/', $content);
      if ($token_begin_count !== $token_end_count) {
        throw new \RuntimeException(sprintf('Invalid begin and end token count in file %s: begin is %s(%s), end is %s(%s).', $filename, $token_begin, $token_begin_count, $token_end, $token_end_count));
      }
    }

    $out = [];
    $within_token = FALSE;

    $lines = file($filename);
    foreach ($lines as $line) {
      if (str_contains($line, (string) $token_begin)) {
        if ($with_content) {
          $within_token = TRUE;
        }
        continue;
      }
      elseif (str_contains($line, (string) $token_end)) {
        if ($with_content) {
          $within_token = FALSE;
        }
        continue;
      }

      if ($with_content && $within_token) {
        // Skip content as contents of the token.
        continue;
      }

      $out[] = $line;
    }

    file_put_contents($filename, implode('', $out));
  }

  protected static function replaceStringFilename($search, $replace, string $dir) {
    $files = static::scandirRecursive($dir, static::ignorePaths());
    foreach ($files as $filename) {
      $new_filename = str_replace($search, $replace, (string) $filename);
      if ($filename != $new_filename) {
        $new_dir = dirname($new_filename);
        if (!is_dir($new_dir)) {
          mkdir($new_dir, 0777, TRUE);
        }
        rename($filename, $new_filename);
      }
    }
  }

  /**
   * Recursively scan directory for files.
   */
  protected static function scandirRecursive(string $dir, $ignore_paths = [], $include_dirs = FALSE): array {
    $discovered = [];

    if (is_dir($dir)) {
      $paths = array_diff(scandir($dir), ['.', '..']);
      foreach ($paths as $path) {
        $path = $dir . '/' . $path;
        foreach ($ignore_paths as $ignore_path) {
          // Exlude based on sub-path match.
          if (str_contains($path, (string) $ignore_path)) {
            continue(2);
          }
        }
        if (is_dir($path)) {
          if ($include_dirs) {
            $discovered[] = $path;
          }
          $discovered = array_merge($discovered, static::scandirRecursive($path, $ignore_paths, $include_dirs));
        }
        else {
          $discovered[] = $path;
        }
      }
    }

    return $discovered;
  }

  protected function globRecursive($pattern, $flags = 0): array|false {
    $files = glob($pattern, $flags | GLOB_BRACE);
    foreach (glob(dirname((string) $pattern) . '/{,.}*[!.]', GLOB_BRACE | GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->globRecursive($dir . '/' . basename((string) $pattern), $flags));
    }

    return $files;
  }

  protected static function ignorePaths(): array {
    return array_merge([
      '/.git/',
      '/.idea/',
      '/vendor/',
      '/node_modules/',
      '/.data/',
    ], static::internalPaths());
  }

  protected static function internalPaths(): array {
    return [
      '/.drevops/installer/install',
      '/LICENSE',
      '/.drevops/docs',
      '/.drevops/tests',
      '/scripts/drevops/utils',
    ];
  }

  protected static function isInternalPath($relative_path): bool {
    $relative_path = '/' . ltrim((string) $relative_path, './');

    return in_array($relative_path, static::internalPaths());
  }

  protected static function fileIsExcludedFromProcessing($filename): int|false {
    $excluded_patterns = [
      '.+\.png',
      '.+\.jpg',
      '.+\.jpeg',
      '.+\.bpm',
      '.+\.tiff',
    ];

    return preg_match('/^(' . implode('|', $excluded_patterns) . ')$/', (string) $filename);
  }

  /**
   * Execute command wrapper.
   */
  protected function doExec($command, array &$output = NULL, &$return_var = NULL): string|false {
    if ($this->isInstallDebug()) {
      $this->status(sprintf('COMMAND: %s', $command), self::INSTALLER_STATUS_DEBUG);
    }
    $result = exec($command, $output, $return_var);
    if ($this->isInstallDebug()) {
      $this->status(sprintf('  OUTPUT: %s', implode('', $output)), self::INSTALLER_STATUS_DEBUG);
      $this->status(sprintf('  CODE  : %s', $return_var), self::INSTALLER_STATUS_DEBUG);
      $this->status(sprintf('  RESULT: %s', $result), self::INSTALLER_STATUS_DEBUG);
    }

    return $result;
  }

  protected static function rmdirRecursive($directory, array $options = []) {
    if (!isset($options['traverseSymlinks'])) {
      $options['traverseSymlinks'] = FALSE;
    }
    $items = glob($directory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_MARK | GLOB_BRACE);
    foreach ($items as $item) {
      if (basename($item) == '.' || basename($item) == '..') {
        continue;
      }
      if (substr($item, -1) == DIRECTORY_SEPARATOR) {
        if (!$options['traverseSymlinks'] && is_link(rtrim($item, DIRECTORY_SEPARATOR))) {
          unlink(rtrim($item, DIRECTORY_SEPARATOR));
        }
        else {
          static::rmdirRecursive($item, $options);
        }
      }
      else {
        unlink($item);
      }
    }
    if (is_dir($directory = rtrim((string) $directory, '\\/'))) {
      if (is_link($directory)) {
        unlink($directory);
      }
      else {
        rmdir($directory);
      }
    }
  }

  protected static function rmdirRecursiveEmpty($directory, $options = []) {
    if (static::dirIsEmpty($directory)) {
      static::rmdirRecursive($directory, $options);
      static::rmdirRecursiveEmpty(dirname((string) $directory), $options);
    }
  }

  protected static function dirIsEmpty($directory): bool {
    return is_dir($directory) && count(scandir($directory)) === 2;
  }

  protected function status(string $message, $level = self::INSTALLER_STATUS_MESSAGE, $eol = TRUE, $use_prefix = TRUE) {
    $prefix = '';
    $color = NULL;

    switch ($level) {
      case self::INSTALLER_STATUS_SUCCESS:
        $prefix = '✓️';
        $color = 'success';
        break;

      case self::INSTALLER_STATUS_ERROR:
        $prefix = '✗';
        $color = 'error';
        break;

      case self::INSTALLER_STATUS_MESSAGE:
        $prefix = 'i️';
        $color = 'info';
        break;

      case self::INSTALLER_STATUS_DEBUG:
        $prefix = '  [D]';
        break;
    }

    if ($level != self::INSTALLER_STATUS_DEBUG || $this->isInstallDebug()) {
      $this->out(($use_prefix ? $prefix . ' ' : '') . $message, $color, $eol);
    }
  }

  protected static function parseDotenv($filename = '.env'): false|array {
    if (!is_readable($filename)) {
      return FALSE;
    }

    $contents = file_get_contents($filename);
    // Replace all # not inside quotes.
    $contents = preg_replace('/#(?=(?:(?:[^"]*"){2})*[^"]*$)/', ';', $contents);

    return parse_ini_string((string) $contents);
  }

  protected static function loadDotenv($filename = '.env', $override_existing = FALSE) {
    $parsed = static::parseDotenv($filename);

    if ($parsed === FALSE) {
      return;
    }

    foreach ($parsed as $var => $value) {
      if (!static::getenvOrDefault($var) || $override_existing) {
        putenv($var . '=' . $value);
      }
    }

    $GLOBALS['_ENV'] = $GLOBALS['_ENV'] ?? [];
    $GLOBALS['_SERVER'] = $GLOBALS['_SERVER'] ?? [];

    if ($override_existing) {
      $GLOBALS['_ENV'] = $parsed + $GLOBALS['_ENV'];
      $GLOBALS['_SERVER'] = $parsed + $GLOBALS['_SERVER'];
    }
    else {
      $GLOBALS['_ENV'] += $parsed;
      $GLOBALS['_SERVER'] += $parsed;
    }
  }

  /**
   * Reliable wrapper to work with environment values.
   */
  protected static function getenvOrDefault($name, $default = NULL) {
    $vars = getenv();

    if (!isset($vars[$name]) || $vars[$name] == '') {
      return $default;
    }

    return $vars[$name];
  }

  public static function tempdir($dir = NULL, $prefix = 'tmp_', $mode = 0700, $max_attempts = 1000): false|string {
    if (is_null($dir)) {
      $dir = sys_get_temp_dir();
    }

    $dir = rtrim((string) $dir, DIRECTORY_SEPARATOR);

    if (!is_dir($dir) || !is_writable($dir)) {
      return FALSE;
    }

    if (strpbrk((string) $prefix, '\\/:*?"<>|') !== FALSE) {
      return FALSE;
    }
    $attempts = 0;

    do {
      $path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
    } while (!mkdir($path, $mode) && $attempts++ < $max_attempts);

    if (!is_dir($path) || !is_writable($path)) {
      throw new \RuntimeException(sprintf('Unable to create temporary directory "%s".', $path));
    }

    return $path;
  }

  protected function commandExists(string $command) {
    $this->doExec('command -v ' . $command, $lines, $ret);
    if ($ret === 1) {
      throw new \RuntimeException(sprintf('Command "%s" does not exist in the current environment.', $command));
    }
  }

  protected static function toHumanName($value): ?string {
    $value = preg_replace('/[^a-zA-Z0-9]/', ' ', (string) $value);
    $value = trim((string) $value);

    return preg_replace('/\s{2,}/', ' ', $value);
  }

  protected static function toMachineName($value, $preserve_chars = []): string {
    $preserve = '';
    foreach ($preserve_chars as $char) {
      $preserve .= preg_quote((string) $char, '/');
    }
    $pattern = '/[^a-zA-Z0-9' . $preserve . ']/';

    $value = preg_replace($pattern, '_', (string) $value);

    return strtolower((string) $value);
  }

  protected static function toCamelCase($value, $capitalise_first = FALSE): string|array {
    $value = str_replace(' ', '', ucwords((string) preg_replace('/[^a-zA-Z0-9]/', ' ', (string) $value)));

    return $capitalise_first ? $value : lcfirst($value);
  }

  protected function toAbbreviation($value, $length = 2, $word_delim = '_'): string|array {
    $value = trim((string) $value);
    $value = str_replace(' ', '_', $value);
    $parts = explode($word_delim, $value);
    if (count($parts) == 1) {
      return strlen($parts[0]) > $length ? substr($parts[0], 0, $length) : $value;
    }

    $value = implode('', array_map(static function ($word) : string {
        return substr($word, 0, 1);
    }, $parts));

    return substr($value, 0, $length);
  }

  protected function executeCallback(string $prefix, $name) {
    $args = func_get_args();
    $args = array_slice($args, 2);

    $name = $this->snakeToPascal($name);

    $callback = [static::class, $prefix . $name];
    if (method_exists($callback[0], $callback[1])) {
      return call_user_func_array($callback, $args);
    }

    return NULL;
  }

  protected function snakeToPascal($string): string {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', (string) $string)));
  }

  protected function getComposerJsonValue($name) {
    $composer_json = $this->getDstDir() . DIRECTORY_SEPARATOR . 'composer.json';
    if (is_readable($composer_json)) {
      $json = json_decode(file_get_contents($composer_json), TRUE);
      if (isset($json[$name])) {
        return $json[$name];
      }
    }

    return NULL;
  }

  protected function getStdinHandle() {
    global $_stdin_handle;
    if (!$_stdin_handle) {
      $h = fopen('php://stdin', 'r');
      $_stdin_handle = stream_isatty($h) || static::getenvOrDefault('DREVOPS_INSTALLER_FORCE_TTY') ? $h : fopen('/dev/tty', 'r+');
    }

    return $_stdin_handle;
  }

  protected function closeStdinHandle() {
    $_stdin_handle = $this->getStdinHandle();
    fclose($_stdin_handle);
  }

  protected function out($text, $color = NULL, $new_line = TRUE) {
    $styles = [
      'success' => "\033[0;32m%s\033[0m",
      'error' => "\033[31;31m%s\033[0m",
    ];

    $format = '%s';

    if (isset($styles[$color]) && $this->getConfig('ANSI')) {
      $format = $styles[$color];
    }

    if ($new_line) {
      $format .= PHP_EOL;
    }

    printf($format, $text);
  }

  protected function debug($value, string $name = '') {
    print PHP_EOL;
    print trim($name . ' DEBUG START') . PHP_EOL;
    print print_r($value, TRUE) . PHP_EOL;
    print trim($name . ' DEBUG FINISH') . PHP_EOL;
    print PHP_EOL;
  }

}
