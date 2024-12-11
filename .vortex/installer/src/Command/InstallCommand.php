<?php

declare(strict_types=1);

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
   */
  protected static string $currentDir;

  /**
   * {@inheritdoc}
   */
  protected static string $defaultName = 'install';

  /**
   * Configures the current command.
   */
  protected function configure(): void {
    $this
      ->setName('Vortex CLI installer')
      ->addArgument('path', InputArgument::OPTIONAL, 'Destination directory. Optional. Defaults to the current directory.')
      ->setHelp($this->getHelpText());
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
    $cwd = getcwd();
    if (!$cwd) {
      throw new \RuntimeException('Unable to determine current working directory.');
    }
    self::$currentDir = $cwd;

    $this->initConfig($input);

    if ($this->getConfig('help')) {
      $output->write($this->getHelpText());

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

  protected function checkRequirements(): void {
    $this->commandExists('git');
    $this->commandExists('tar');
    $this->commandExists('composer');
  }

  protected function install(): void {
    $this->download();

    $this->prepareDestination();

    $this->replaceTokens();

    $this->copyFiles();

    $this->processDemo();
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
    $dir = $this->getConfig('VORTEX_INSTALL_TMP_DIR');

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
    $src = $this->getConfig('VORTEX_INSTALL_TMP_DIR');
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
      static::rmdirRecursiveEmpty($dir);
    }

    // Src directory is now "clean" - copy it to dst directory.
    if (is_dir($src) && !static::dirIsEmpty($src)) {
      static::copyRecursive($src, $dst, 0755, FALSE);
    }

    // Special case for .env.local as it may exist.
    if (!file_exists($dst . '/.env.local')) {
      static::copyRecursive($dst . '/.env.local.example', $dst . '/.env.local', 0755, FALSE);
    }
  }

  protected function processDemo(): void {
    if (empty($this->getConfig('VORTEX_INSTALL_DEMO')) || !empty($this->getConfig('VORTEX_INSTALL_DEMO_SKIP'))) {
      return;
    }

    // Reload variables from destination's .env.
    static::loadDotenv($this->getDstDir() . '/.env');

    $url = static::getenvOrDefault('VORTEX_DB_DOWNLOAD_CURL_URL');
    if (empty($url)) {
      return;
    }

    $data_dir = $this->getDstDir() . DIRECTORY_SEPARATOR . static::getenvOrDefault('VORTEX_DB_DIR', './.data');
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

  protected static function copyRecursive(string $source, string $dest, int $permissions = 0755, bool $copy_empty_dirs = FALSE): bool {
    $parent = dirname($dest);

    if (!is_dir($parent)) {
      mkdir($parent, $permissions, TRUE);
    }

    // Note that symlink target must exist.
    if (is_link($source)) {
      // Changing dir symlink will be relevant to the current destination's file
      // directory.
      $cur_dir = getcwd();

      if (!$cur_dir) {
        throw new \RuntimeException('Unable to determine current working directory.');
      }

      chdir($parent);
      $ret = TRUE;

      if (!is_readable(basename($dest))) {
        $link = readlink($source);
        if ($link) {
          $ret = symlink($link, basename($dest));
        }
      }

      chdir($cur_dir);

      return $ret;
    }

    if (is_file($source)) {
      $ret = copy($source, $dest);
      if ($ret) {
        $perms = fileperms($source);
        if ($perms !== FALSE) {
          chmod($dest, $perms);
        }
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

  protected function gitFileIsTracked(string $path, string $dir): bool {
    if (is_dir($dir . DIRECTORY_SEPARATOR . '.git')) {
      $cwd = getcwd();
      if (!$cwd) {
        throw new \RuntimeException('Unable to determine current working directory.');
      }

      chdir($dir);
      $this->doExec(sprintf('git ls-files --error-unmatch "%s" 2>&1 >/dev/null', $path), $output, $code);
      chdir($cwd);

      return $code === 0;
    }

    return FALSE;
  }

  /**
   * Get core profiles names.
   *
   * @return array<int, string>
   *   Array of core profiles names.
   */
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
  protected function processAnswer(string $name, string $dir): mixed {
    return $this->executeCallback('process', $name, $dir);
  }

  protected function processProfile(string $dir): void {
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

  protected function processProvisionUseProfile(string $dir): void {
    if ($this->getAnswer('provision_use_profile') === self::ANSWER_YES) {
      static::fileReplaceContent('/VORTEX_PROVISION_USE_PROFILE=.*/', "VORTEX_PROVISION_USE_PROFILE=1", $dir . '/.env');
      $this->removeTokenWithContent('!PROVISION_USE_PROFILE', $dir);
    }
    else {
      static::fileReplaceContent('/VORTEX_PROVISION_USE_PROFILE=.*/', "VORTEX_PROVISION_USE_PROFILE=0", $dir . '/.env');
      $this->removeTokenWithContent('PROVISION_USE_PROFILE', $dir);
    }
  }

  protected function processDatabaseDownloadSource(string $dir): void {
    $type = $this->getAnswer('database_download_source');
    static::fileReplaceContent('/VORTEX_DB_DOWNLOAD_SOURCE=.*/', 'VORTEX_DB_DOWNLOAD_SOURCE=' . $type, $dir . '/.env');

    $types = [
      'curl',
      'ftp',
      'acquia',
      'lagoon',
      'container_registry',
      'none',
    ];

    foreach ($types as $t) {
      $token = 'VORTEX_DB_DOWNLOAD_SOURCE_' . strtoupper($t);
      if ($t === $type) {
        $this->removeTokenWithContent('!' . $token, $dir);
      }
      else {
        $this->removeTokenWithContent($token, $dir);
      }
    }
  }

  protected function processDatabaseImage(string $dir): void {
    $image = $this->getAnswer('database_image');
    static::fileReplaceContent('/VORTEX_DB_IMAGE=.*/', 'VORTEX_DB_IMAGE=' . $image, $dir . '/.env');

    if ($image !== '' && $image !== '0') {
      $this->removeTokenWithContent('!VORTEX_DB_IMAGE', $dir);
    }
    else {
      $this->removeTokenWithContent('VORTEX_DB_IMAGE', $dir);
    }
  }

  protected function processOverrideExistingDb(string $dir): void {
    if ($this->getAnswer('override_existing_db') === self::ANSWER_YES) {
      static::fileReplaceContent('/VORTEX_PROVISION_OVERRIDE_DB=.*/', "VORTEX_PROVISION_OVERRIDE_DB=1", $dir . '/.env');
    }
    else {
      static::fileReplaceContent('/VORTEX_PROVISION_OVERRIDE_DB=.*/', "VORTEX_PROVISION_OVERRIDE_DB=0", $dir . '/.env');
    }
  }

  protected function processCiProvider(string $dir): void {
    $type = $this->getAnswer('ci_provider');

    $remove_gha = FALSE;
    $remove_circleci = FALSE;

    switch ($type) {
      case 'CircleCI':
        $remove_gha = TRUE;
        break;

      case 'GitHub Actions':
        $remove_circleci = TRUE;
        break;

      default:
        $remove_circleci = TRUE;
        $remove_gha = TRUE;
    }

    if ($remove_gha) {
      @unlink($dir . '/.github/workflows/build-test-deploy.yml');
      $this->removeTokenWithContent('CI_PROVIDER_GHA', $dir);
    }

    if ($remove_circleci) {
      static::rmdirRecursive($dir . '/.circleci');
      @unlink($dir . '/tests/phpunit/CircleCiConfigTest.php');
      $this->removeTokenWithContent('CI_PROVIDER_CIRCLECI', $dir);
    }

    if ($remove_gha && $remove_circleci) {
      @unlink($dir . '/docs/ci.md');
      $this->removeTokenWithContent('CI_PROVIDER_ANY', $dir);
    }
    else {
      $this->removeTokenWithContent('!CI_PROVIDER_ANY', $dir);
    }
  }

  protected function processDeployType(string $dir): void {
    $type = $this->getAnswer('deploy_type');
    if ($type !== 'none') {
      static::fileReplaceContent('/VORTEX_DEPLOY_TYPES=.*/', 'VORTEX_DEPLOY_TYPES=' . $type, $dir . '/.env');

      if (!str_contains($type, 'artifact')) {
        @unlink($dir . '/.gitignore.deployment');
        @unlink($dir . '/.gitignore.artifact');
      }

      $this->removeTokenWithContent('!DEPLOYMENT', $dir);
    }
    else {
      @unlink($dir . '/docs/deployment.md');
      @unlink($dir . '/.gitignore.deployment');
      @unlink($dir . '/.gitignore.artifact');
      $this->removeTokenWithContent('DEPLOYMENT', $dir);
    }
  }

  protected function processPreserveAcquia(string $dir): void {
    if ($this->getAnswer('preserve_acquia') === self::ANSWER_YES) {
      $this->removeTokenWithContent('!ACQUIA', $dir);
    }
    else {
      static::rmdirRecursive($dir . '/hooks');
      $webroot = $this->getAnswer('webroot');
      @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.acquia.php', $dir, $webroot));
      $this->removeTokenWithContent('ACQUIA', $dir);
    }
  }

  protected function processPreserveLagoon(string $dir): void {
    if ($this->getAnswer('preserve_lagoon') === self::ANSWER_YES) {
      $this->removeTokenWithContent('!LAGOON', $dir);
    }
    else {
      @unlink($dir . '/drush/sites/lagoon.site.yml');
      @unlink($dir . '/.lagoon.yml');
      @unlink($dir . '/.github/workflows/close-pull-request.yml');
      $webroot = $this->getAnswer('webroot');
      @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.lagoon.php', $dir, $webroot));
      $this->removeTokenWithContent('LAGOON', $dir);
    }
  }

  protected function processPreserveFtp(string $dir): void {
    if ($this->getAnswer('preserve_ftp') === self::ANSWER_YES) {
      $this->removeTokenWithContent('!FTP', $dir);
    }
    else {
      $this->removeTokenWithContent('FTP', $dir);
    }
  }

  protected function processPreserveRenovatebot(string $dir): void {
    if ($this->getAnswer('preserve_renovatebot') === self::ANSWER_YES) {
      $this->removeTokenWithContent('!RENOVATEBOT', $dir);
    }
    else {
      @unlink($dir . '/renovate.json');
      $this->removeTokenWithContent('RENOVATEBOT', $dir);
    }
  }

  protected function processStringTokens(string $dir): void {
    $machine_name_hyphenated = str_replace('_', '-', $this->getAnswer('machine_name'));
    $machine_name_camel_cased = static::toCamelCase($this->getAnswer('machine_name'), TRUE);
    $module_prefix_camel_cased = static::toCamelCase($this->getAnswer('module_prefix'), TRUE);
    $module_prefix_uppercase = strtoupper($module_prefix_camel_cased);
    $theme_camel_cased = static::toCamelCase($this->getAnswer('theme'), TRUE);
    $vortex_version_urlencoded = str_replace('-', '--', (string) $this->getConfig('VORTEX_VERSION'));
    $url = $this->getAnswer('url');
    $host = parse_url($url, PHP_URL_HOST);
    $domain = $host ?: $url;
    $domain_non_www = str_starts_with((string) $domain, "www.") ? substr((string) $domain, 4) : $domain;
    $webroot = $this->getAnswer('webroot');

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    static::dirReplaceContent('your_site_theme',       $this->getAnswer('theme'),                     $dir);
    static::dirReplaceContent('YourSiteTheme',         $theme_camel_cased,                            $dir);
    static::dirReplaceContent('your_org',              $this->getAnswer('org_machine_name'),          $dir);
    static::dirReplaceContent('YOURORG',               $this->getAnswer('org'),                       $dir);
    static::dirReplaceContent('www.your-site-url.example',  $domain,                                  $dir);
    static::dirReplaceContent('your-site-url.example',      $domain_non_www,                          $dir);
    static::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/modules/custom', $webroot));
    static::dirReplaceContent('ys_search',             $this->getAnswer('module_prefix') . '_search', $dir . sprintf('/%s/modules/custom', $webroot));
    static::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/themes/custom',  $webroot));
    static::dirReplaceContent('ys_core',               $this->getAnswer('module_prefix') . '_core',   $dir . '/scripts/custom');
    static::dirReplaceContent('ys_search',             $this->getAnswer('module_prefix') . '_search', $dir . '/scripts/custom');
    static::dirReplaceContent('YsCore',                $module_prefix_camel_cased . 'Core',           $dir . sprintf('/%s/modules/custom', $webroot));
    static::dirReplaceContent('YsSearch',              $module_prefix_camel_cased . 'Search',         $dir . sprintf('/%s/modules/custom', $webroot));
    static::dirReplaceContent('YSCODE',                $module_prefix_uppercase,                      $dir);
    static::dirReplaceContent('YSSEARCH',              $module_prefix_uppercase,                      $dir);
    static::dirReplaceContent('your-site',             $machine_name_hyphenated,                      $dir);
    static::dirReplaceContent('your_site',             $this->getAnswer('machine_name'),              $dir);
    static::dirReplaceContent('YOURSITE',              $this->getAnswer('name'),                      $dir);
    static::dirReplaceContent('YourSite',              $machine_name_camel_cased,                     $dir);

    static::replaceStringFilename('YourSiteTheme',     $theme_camel_cased,                            $dir);
    static::replaceStringFilename('your_site_theme',   $this->getAnswer('theme'),                     $dir);
    static::replaceStringFilename('YourSite',          $machine_name_camel_cased,                     $dir);
    static::replaceStringFilename('ys_core',           $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/modules/custom', $webroot));
    static::replaceStringFilename('ys_search',         $this->getAnswer('module_prefix') . '_search', $dir . sprintf('/%s/modules/custom', $webroot));
    static::replaceStringFilename('YsCore',            $module_prefix_camel_cased . 'Core',           $dir . sprintf('/%s/modules/custom', $webroot));
    static::replaceStringFilename('your_org',          $this->getAnswer('org_machine_name'),          $dir);
    static::replaceStringFilename('your_site',         $this->getAnswer('machine_name'),              $dir);

    static::dirReplaceContent('VORTEX_VERSION_URLENCODED', $vortex_version_urlencoded,                $dir);
    static::dirReplaceContent('VORTEX_VERSION',            $this->getConfig('VORTEX_VERSION'),        $dir);
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
  }

  protected function processPreserveDocComments(string $dir): void {
    if ($this->getAnswer('preserve_doc_comments') === self::ANSWER_YES) {
      // Replace special "#: " comments with normal "#" comments.
      static::dirReplaceContent('#:', '#', $dir);
    }
    else {
      $this->removeTokenLine('#:', $dir);
    }
  }

  protected function processDemoMode(string $dir): void {
    // Only discover demo mode if not explicitly set.
    if (is_null($this->getConfig('VORTEX_INSTALL_DEMO'))) {
      if ($this->getAnswer('provision_use_profile') === self::ANSWER_NO) {
        $download_source = $this->getAnswer('database_download_source');
        $db_file = static::getenvOrDefault('VORTEX_DB_DIR', './.data') . DIRECTORY_SEPARATOR . static::getenvOrDefault('VORTEX_DB_FILE', 'db.sql');
        $has_comment = static::fileContains('to allow to demonstrate how Vortex works without', $this->getDstDir() . '/.env');

        // Enable Vortex demo mode if download source is file AND
        // there is no downloaded file present OR if there is a demo comment in
        // destination .env file.
        if ($download_source !== 'container_registry') {
          if ($has_comment || !file_exists($db_file)) {
            $this->setConfig('VORTEX_INSTALL_DEMO', TRUE);
          }
          else {
            $this->setConfig('VORTEX_INSTALL_DEMO', FALSE);
          }
        }
        elseif ($has_comment) {
          $this->setConfig('VORTEX_INSTALL_DEMO', TRUE);
        }
        else {
          $this->setConfig('VORTEX_INSTALL_DEMO', FALSE);
        }
      }
      else {
        $this->setConfig('VORTEX_INSTALL_DEMO', FALSE);
      }
    }

    if (!$this->getConfig('VORTEX_INSTALL_DEMO')) {
      $this->removeTokenWithContent('DEMO', $dir);
    }
  }

  protected function processPreserveVortexInfo(string $dir): void {
    if ($this->getAnswer('preserve_vortex_info') === self::ANSWER_NO) {
      // Remove code required for Vortex maintenance.
      $this->removeTokenWithContent('VORTEX_DEV', $dir);

      // Remove all other comments.
      $this->removeTokenLine('#;', $dir);
    }
  }

  protected function processVortexInternal(string $dir): void {
    if (file_exists($dir . DIRECTORY_SEPARATOR . 'README.dist.md')) {
      rename($dir . DIRECTORY_SEPARATOR . 'README.dist.md', $dir . DIRECTORY_SEPARATOR . 'README.md');
    }

    // Remove Vortex internal files.
    static::rmdirRecursive($dir . DIRECTORY_SEPARATOR . '.vortex');

    @unlink($dir . '/.github/FUNDING.yml');
    @unlink($dir . 'CODE_OF_CONDUCT.md');
    @unlink($dir . 'CONTRIBUTING.md');
    @unlink($dir . 'LICENSE');
    @unlink($dir . 'SECURITY.md');

    // Remove Vortex internal GHAs.
    $files = glob($dir . '/.github/workflows/vortex-*.yml');
    if ($files) {
      foreach ($files as $file) {
        @unlink($file);
      }
    }

    // Remove other unhandled tokenized comments.
    $this->removeTokenLine('#;<', $dir);
    $this->removeTokenLine('#;>', $dir);
  }

  protected function processEnableCommentedCode(string $dir): void {
    // Enable_commented_code.
    static::dirReplaceContent('##### ', '', $dir);
  }

  protected function processWebroot(string $dir): void {
    $new_name = $this->getAnswer('webroot', 'web');

    if ($new_name !== 'web') {
      static::dirReplaceContent('web/', $new_name . '/', $dir);
      static::dirReplaceContent('web\/', $new_name . '\/', $dir);
      static::dirReplaceContent(': web', ': ' . $new_name, $dir);
      static::dirReplaceContent('=web', '=' . $new_name, $dir);
      static::dirReplaceContent('!web', '!' . $new_name, $dir);
      static::dirReplaceContent('/\/web\//', '/' . $new_name . '/', $dir);
      static::dirReplaceContent('/\'\/web\'/', "'/" . $new_name . "'", $dir);
      rename($dir . DIRECTORY_SEPARATOR . 'web', $dir . DIRECTORY_SEPARATOR . $new_name);
    }
  }

  /**
   * Download Vortex source files.
   */
  protected function download(): void {
    if ($this->getConfig('VORTEX_INSTALL_LOCAL_REPO')) {
      $this->downloadLocal();
    }
    else {
      $this->downloadRemote();
    }
  }

  protected function downloadLocal(): void {
    $dst = $this->getConfig('VORTEX_INSTALL_TMP_DIR');
    $repo = $this->getConfig('VORTEX_INSTALL_LOCAL_REPO');
    $ref = $this->getConfig('VORTEX_INSTALL_COMMIT');

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
    $dst = $this->getConfig('VORTEX_INSTALL_TMP_DIR');
    $org = 'drevops';
    $project = 'vortex';
    $ref = $this->getConfig('VORTEX_INSTALL_COMMIT');
    $release_prefix = $this->getConfig('VORTEX_VERSION');

    if ($ref == 'HEAD') {
      $release_prefix = $release_prefix == 'develop' ? NULL : $release_prefix;
      $ref = $this->findLatestVortexRelease($org, $project, $release_prefix);
      $this->setConfig('VORTEX_VERSION', $ref);
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

  protected function askShouldProceed(): bool {
    $proceed = self::ANSWER_YES;

    if (!$this->isQuiet()) {
      $proceed = $this->ask(sprintf('Proceed with installing Vortex into your project\'s directory "%s"? (Y,n)', $this->getDstDir()), $proceed, TRUE);
    }

    // Kill-switch to not proceed with install. If false, the install will not
    // proceed despite the answer received above.
    if (!$this->getConfig('VORTEX_INSTALL_PROCEED')) {
      $proceed = self::ANSWER_NO;
    }

    return strtolower((string) $proceed) === self::ANSWER_YES;
  }

  protected function askForAnswer(string $name, string $question): void {
    $discovered = $this->discoverValue($name);
    $answer = $this->ask($question, $discovered);
    $answer = $this->normaliseAnswer($name, $answer);

    $this->setAnswer($name, $answer);
  }

  protected function ask(string $question, ?string $default, bool $close_handle = FALSE): ?string {
    if ($this->isQuiet()) {
      return $default;
    }

    $question = sprintf('> %s [%s] ', $question, $default);

    $this->out($question, 'question', FALSE);
    $handle = $this->getStdinHandle();
    $answer = fgets($handle);
    if ($answer !== FALSE) {
      $answer = trim($answer);
    }

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
  protected function getConfig(string $name, mixed $default = NULL): mixed {
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
  protected function setConfig(string $name, mixed $value): void {
    global $_config;

    if (!is_null($value)) {
      $_config[$name] = $value;
    }
  }

  /**
   * Get a named option from discovered answers for the project bing installed.
   */
  protected function getAnswer(string $name, mixed $default = NULL): ?string {
    global $_answers;

    return $_answers[$name] ?? $default;
  }

  /**
   * Set a named option for discovered answers for the project bing installed.
   */
  protected function setAnswer(string $name, mixed $value): void {
    global $_answers;
    $_answers[$name] = $value;
  }

  /**
   * Get all options from discovered answers for the project bing installed.
   *
   * @return array<string, mixed>
   *   Array of all discovered answers.
   */
  protected function getAnswers(): array {
    global $_answers;

    return $_answers;
  }

  /**
   * Init all config.
   */
  protected function initConfig(InputInterface $input): void {
    $this->initCliArgsAndOptions($input);

    static::loadDotenv($this->getDstDir() . '/.env');

    $this->initInstallerConfig();
  }

  /**
   * Initialise CLI options.
   */
  protected function initCliArgsAndOptions(InputInterface $input): void {
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
      $is_ansi = (DIRECTORY_SEPARATOR === '\\')
        ? (FALSE !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI'))
        : (function_exists('posix_isatty') && posix_isatty(1));
      $this->setConfig('ANSI', $is_ansi);
    }

    if (!empty($arg['path'])) {
      $this->setConfig('VORTEX_INSTALL_DST_DIR', $arg['path']);
    }
    else {
      $this->setConfig('VORTEX_INSTALL_DST_DIR', static::getenvOrDefault('VORTEX_INSTALL_DST_DIR', self::$currentDir));
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
  protected function initInstallerConfig(): void {
    // Internal version of Vortex.
    $this->setConfig('VORTEX_VERSION', static::getenvOrDefault('VORTEX_VERSION', 'develop'));
    // Flag to display install debug information.
    $this->setConfig('VORTEX_INSTALL_DEBUG', (bool) static::getenvOrDefault('VORTEX_INSTALL_DEBUG', FALSE));
    // Flag to proceed with installation. If FALSE - the installation will only
    // print resolved values and will not proceed.
    $this->setConfig('VORTEX_INSTALL_PROCEED', (bool) static::getenvOrDefault('VORTEX_INSTALL_PROCEED', TRUE));
    // Temporary directory to download and expand files to.
    $this->setConfig('VORTEX_INSTALL_TMP_DIR', static::getenvOrDefault('VORTEX_INSTALL_TMP_DIR', static::tempdir()));
    // Path to local Vortex repository. If not provided - remote will be used.
    $this->setConfig('VORTEX_INSTALL_LOCAL_REPO', static::getenvOrDefault('VORTEX_INSTALL_LOCAL_REPO'));
    // Optional commit to download. If not provided, latest release will be
    // downloaded.
    $this->setConfig('VORTEX_INSTALL_COMMIT', static::getenvOrDefault('VORTEX_INSTALL_COMMIT', 'HEAD'));

    // Internal flag to enforce DEMO mode. If not set, the demo mode will be
    // discovered automatically.
    if (!is_null(static::getenvOrDefault('VORTEX_INSTALL_DEMO'))) {
      $this->setConfig('VORTEX_INSTALL_DEMO', (bool) static::getenvOrDefault('VORTEX_INSTALL_DEMO'));
    }
    // Internal flag to skip processing of the demo mode.
    $this->setConfig('VORTEX_INSTALL_DEMO_SKIP', (bool) static::getenvOrDefault('VORTEX_INSTALL_DEMO_SKIP', FALSE));
  }

  protected function getDstDir(): ?string {
    return $this->getConfig('VORTEX_INSTALL_DST_DIR');
  }

  /**
   * Shorthand to get the value of whether install should be quiet.
   */
  protected function isQuiet(): bool {
    return (bool) $this->getConfig('quiet', FALSE);
  }

  /**
   * Shorthand to get the value of VORTEX_INSTALL_DEBUG.
   */
  protected function isInstallDebug(): bool {
    return (bool) $this->getConfig('VORTEX_INSTALL_DEBUG', FALSE);
  }

  /**
   * Get default value router.
   */
  protected function getDefaultValue(string $name): mixed {
    // Allow to override default values from config variables.
    $config_name = strtoupper($name);

    return $this->getConfig($config_name, $this->executeCallback('getDefaultValue', $name));
  }

  protected function getDefaultValueName(): ?string {
    return static::toHumanName(static::getenvOrDefault('VORTEX_PROJECT', basename((string) $this->getDstDir())));
  }

  protected function getDefaultValueMachineName(): ?string {
    return static::toMachineName($this->getAnswer('name', 'your_site'));
  }

  protected function getDefaultValueOrg(): string {
    return $this->getAnswer('name', 'Your Site') . ' Org';
  }

  protected function getDefaultValueOrgMachineName(): string {
    return static::toMachineName($this->getAnswer('org'));
  }

  protected function getDefaultValueModulePrefix(): string {
    return $this->toAbbreviation($this->getAnswer('machine_name'));
  }

  protected function getDefaultValueProfile(): string {
    return self::ANSWER_NO;
  }

  protected function getDefaultValueTheme(): mixed {
    return $this->getAnswer('machine_name');
  }

  protected function getDefaultValueUrl(): string {
    $value = $this->getAnswer('machine_name');
    $value = str_replace('_', '-', $value);

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

  protected function getDefaultValueCiProvider(): string {
    return 'GitHub Actions';
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

  protected function getDefaultValuePreserveVortexInfo(): string {
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
  protected function discoverValue(string $name): mixed {
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

  protected function discoverValueModulePrefix(): ?string {
    $webroot = $this->getAnswer('webroot');

    $locations = [
      $this->getDstDir() . sprintf('/%s/modules/custom/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/sites/all/modules/custom/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/*/modules/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/*/modules/custom/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/custom/*/modules/*_core', $webroot),
      $this->getDstDir() . sprintf('/%s/profiles/custom/*/modules/custom/*_core', $webroot),
    ];

    $path = $this->findMatchingPath($locations);

    if (empty($path)) {
      return NULL;
    }

    $path = basename($path);

    return str_replace('_core', '', $path);
  }

  protected function discoverValueProfile(): ?string {
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

    $name = basename($name);

    return str_replace(['.info.yml', '.info'], '', $name);
  }

  protected function discoverValueTheme(): ?string {
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

    $name = basename($name);

    return str_replace(['.info.yml', '.info'], '', $name);
  }

  protected function discoverValueUrl(): ?string {
    $webroot = $this->getAnswer('webroot');

    $origin = NULL;
    $path = $this->getDstDir() . sprintf('/%s/sites/default/settings.php', $webroot);

    if (!is_readable($path)) {
      return NULL;
    }

    $contents = file_get_contents($path);
    if (!$contents) {
      return NULL;
    }

    // Drupal 8 and 9.
    if (preg_match('/\$config\s*\[\'stage_file_proxy.settings\'\]\s*\[\'origin\'\]\s*=\s*[\'"]([^\'"]+)[\'"];/', $contents, $matches)) {
      $origin = $matches[1];
    }
    // Drupal 7.
    elseif (preg_match('/\$conf\s*\[\'stage_file_proxy_origin\'\]\s*=\s*[\'"]([^\'"]+)[\'"];/', $contents, $matches)) {
      $origin = $matches[1];
    }

    if ($origin) {
      $origin = parse_url($origin, PHP_URL_HOST);
    }

    return empty($origin) ? NULL : $origin;
  }

  protected function discoverValueWebroot(): ?string {
    $webroot = $this->getValueFromDstDotenv('VORTEX_WEBROOT');

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
    return $this->getValueFromDstDotenv('VORTEX_PROVISION_USE_PROFILE') ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValueDatabaseDownloadSource(): ?string {
    return $this->getValueFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');
  }

  protected function discoverValueDatabaseStoreType(): string {
    return $this->discoverValueDatabaseImage() ? 'container_image' : 'file';
  }

  protected function discoverValueDatabaseImage(): ?string {
    return $this->getValueFromDstDotenv('VORTEX_DB_IMAGE');
  }

  protected function discoverValueOverrideExistingDb(): string {
    return $this->getValueFromDstDotenv('VORTEX_PROVISION_OVERRIDE_DB') ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValueCiProvider(): ?string {
    if (is_readable($this->getDstDir() . '/.github/workflows/build-test-deploy.yml')) {
      return 'GitHub Actions';
    }

    if (is_readable($this->getDstDir() . '/.circleci/config.yml')) {
      return 'CircleCI';
    }

    return $this->isInstalled() ? 'none' : NULL;
  }

  protected function discoverValueDeployType(): ?string {
    return $this->getValueFromDstDotenv('VORTEX_DEPLOY_TYPES');
  }

  protected function discoverValuePreserveAcquia(): ?string {
    if (is_readable($this->getDstDir() . '/hooks')) {
      return self::ANSWER_YES;
    }

    $value = $this->getValueFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');

    if (is_null($value)) {
      return NULL;
    }

    return $value == 'acquia' ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveLagoon(): ?string {
    if (is_readable($this->getDstDir() . '/.lagoon.yml')) {
      return self::ANSWER_YES;
    }

    if ($this->getAnswer('deploy_type') === 'lagoon') {
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
    $value = $this->getValueFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');
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

  protected function discoverValuePreserveVortexInfo(): ?string {
    $file = $this->getDstDir() . '/.ahoy.yml';
    if (!is_readable($file)) {
      return NULL;
    }

    return static::fileContains('Comments starting with', $file) ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function getValueFromDstDotenv(string $name, mixed $default = NULL): mixed {
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

    return $parsed !== [] ? $parsed[$name] ?? $default : $default;
  }

  /**
   * Find a matching path using glob.
   *
   * @param array<int, string>|string $paths
   *   Array of paths wildcards to search.
   * @param string|null $text
   *   Optional text to search in the files.
   *
   * @return string|null
   *   Path to the file or NULL if not found.
   */
  protected function findMatchingPath(array|string $paths, ?string $text = NULL): ?string {
    $paths = is_array($paths) ? $paths : [$paths];

    foreach ($paths as $path) {
      $files = glob($path);

      if (empty($files)) {
        continue;
      }

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

    return NULL;
  }

  /**
   * Check that Vortex is installed for this project.
   */
  protected function isInstalled(): bool {
    $path = $this->getDstDir() . DIRECTORY_SEPARATOR . 'README.md';

    if (!file_exists($path)) {
      return FALSE;
    }

    $content = file_get_contents($path);
    if (!$content) {
      return FALSE;
    }

    return (bool) preg_match('/badge\/Vortex\-/', $content);
  }

  /**
   * Normalisation router.
   */
  protected function normaliseAnswer(string $name, mixed $value): mixed {
    $normalised = $this->executeCallback('normaliseAnswer', $name, strval($value));

    return $normalised ?? $value;
  }

  protected function normaliseAnswerName(string $value): string {
    return ucfirst((string) static::toHumanName($value));
  }

  protected function normaliseAnswerMachineName(string $value): string {
    return static::toMachineName($value);
  }

  protected function normaliseAnswerOrgMachineName(string $value): string {
    return static::toMachineName($value);
  }

  protected function normaliseAnswerModulePrefix(string $value): string {
    return static::toMachineName($value);
  }

  protected function normaliseAnswerProfile(string $value): string {
    $profile = static::toMachineName($value);

    if (empty($profile) || strtolower($profile) === self::ANSWER_NO) {
      $profile = 'standard';
    }

    return $profile;
  }

  protected function normaliseAnswerTheme(string $value): string {
    return static::toMachineName($value);
  }

  protected function normaliseAnswerUrl(string $url): string {
    $url = trim($url);

    return str_replace([' ', '_'], '-', $url);
  }

  protected function normaliseAnswerWebroot(string $value): string {
    return strtolower(trim($value, '/'));
  }

  protected function normaliseAnswerProvisionUseProfile(string $value): string {
    return strtolower($value) !== self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerDatabaseDownloadSource(string $value): string {
    $value = strtolower($value);

    return match ($value) {
      'f', 'ftp' => 'ftp',
      'a', 'acquia' => 'acquia',
      'i', 'image', 'container_image', 'container_registry' => 'container_registry',
      'c', 'curl' => 'curl',
      default => $this->getDefaultValueDatabaseDownloadSource(),
    };
  }

  protected function normaliseAnswerDatabaseStoreType(string $value): string {
    $value = strtolower($value);

    return match ($value) {
      'i', 'image', 'container_image', => 'container_image',
      'f', 'file' => 'file',
      default => $this->getDefaultValueDatabaseStoreType(),
    };
  }

  protected function normaliseAnswerDatabaseImage(string $value): string {
    $value = static::toMachineName($value, ['-', '/', ':', '.']);

    return str_contains($value, ':') ? $value : $value . ':latest';
  }

  protected function normaliseAnswerOverrideExistingDb(string $value): string {
    return strtolower($value) !== self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerCiProvider(string $value): string {
    $value = trim(strtolower($value));

    return match ($value) {
      'c', 'circleci' => 'CircleCI',
      'g', 'gha', 'github actions' => 'GitHub Actions',
      default => 'none',
    };
  }

  protected function normaliseAnswerDeployType(string $value): ?string {
    $types = explode(',', $value);

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

        case 'r':
        case 'container_registry':
          $normalised[] = 'container_registry';
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

    // @todo Should we return `none` instead of `NULL`?
    if (in_array('none', $normalised)) {
      return NULL;
    }

    $normalised = array_unique($normalised);

    return implode(',', $normalised);
  }

  protected function normaliseAnswerPreserveAcquia(string $value): string {
    return strtolower($value) !== self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveLagoon(string $value): string {
    return strtolower($value) !== self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveFtp(string $value): string {
    return strtolower($value) !== self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveRenovatebot(string $value): string {
    return strtolower($value) !== self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveDocComments(string $value): string {
    return strtolower($value) !== self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  protected function normaliseAnswerPreserveVortexInfo(string $value): string {
    return strtolower($value) !== self::ANSWER_YES ? self::ANSWER_NO : self::ANSWER_YES;
  }

  /**
   * Print help.
   */
  protected function getHelpText(): string {
    return <<<EOF
  php install destination

  php install --quiet destination

EOF;
  }

  protected function printHeader(): void {
    if ($this->isQuiet()) {
      $this->printHeaderQuiet();
    }
    else {
      $this->printHeaderInteractive();
    }
    print PHP_EOL;
  }

  protected function printHeaderInteractive(): void {
    $commit = $this->getConfig('VORTEX_INSTALL_COMMIT');

    $content = '';
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $commit) . PHP_EOL;
    }
    $content .= PHP_EOL;
    if ($this->isInstalled()) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }
    $content .= 'Please answer the questions below to install configuration relevant to your site.' . PHP_EOL;
    $content .= 'No changes will be applied until the last confirmation step.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Press Ctrl+C at any time to exit this installer.' . PHP_EOL;

    $this->printBox($content, 'WELCOME TO VORTEX INTERACTIVE INSTALLER');
  }

  protected function printHeaderQuiet(): void {
    $commit = $this->getConfig('VORTEX_INSTALL_COMMIT');

    $content = '';
    if ($commit == 'HEAD') {
      $content .= 'This will install the latest version of Vortex into your project.' . PHP_EOL;
    }
    else {
      $content .= sprintf('This will install Vortex into your project at commit "%s".', $commit) . PHP_EOL;
    }

    $content .= PHP_EOL;
    if ($this->isInstalled()) {
      $content .= 'It looks like Vortex is already installed into this project.' . PHP_EOL;
      $content .= PHP_EOL;
    }

    $content .= 'Vortex installer will try to discover the settings from the environment and will install configuration relevant to your site.' . PHP_EOL;
    $content .= PHP_EOL;
    $content .= 'Existing committed files will be modified. You will need to resolve changes manually.' . PHP_EOL;

    $this->printBox($content, 'WELCOME TO VORTEX QUIET INSTALLER');
  }

  protected function printSummary(): void {
    $values['Current directory'] = self::$currentDir;
    $values['Destination directory'] = $this->getDstDir();
    $values['Vortex version'] = $this->getConfig('VORTEX_VERSION');
    $values['Vortex commit'] = $this->formatNotEmpty($this->getConfig('VORTEX_INSTALL_COMMIT'), 'Latest');

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
    $values['Database store type'] = empty($image) ? 'file' : 'container_image';

    if ($image !== '' && $image !== '0') {
      $values['Database image name'] = $image;
    }

    $values['Override existing database'] = $this->formatYesNo($this->getAnswer('override_existing_db'));
    $values['CI provider'] = $this->formatNotEmpty($this->getAnswer('ci_provider'), 'None');
    $values['Deployment'] = $this->formatNotEmpty($this->getAnswer('deploy_type'), 'Disabled');
    $values['FTP integration'] = $this->formatEnabled($this->getAnswer('preserve_ftp'));
    $values['Acquia integration'] = $this->formatEnabled($this->getAnswer('preserve_acquia'));
    $values['Lagoon integration'] = $this->formatEnabled($this->getAnswer('preserve_lagoon'));
    $values['RenovateBot integration'] = $this->formatEnabled($this->getAnswer('preserve_renovatebot'));
    $values['Preserve docs in comments'] = $this->formatYesNo($this->getAnswer('preserve_doc_comments'));
    $values['Preserve Vortex comments'] = $this->formatYesNo($this->getAnswer('preserve_vortex_info'));

    $content = $this->formatValuesList($values, '', 80 - 2 - 2 * 2);

    $this->printBox($content, 'INSTALLATION SUMMARY');
  }

  protected function printAbort(): void {
    $this->printBox('Aborting project installation. No files were changed.');
  }

  protected function printFooter(): void {
    print PHP_EOL;

    if ($this->isInstalled()) {
      $this->printBox('Finished updating Vortex. Review changes and commit required files.');
    }
    else {
      $this->printBox('Finished installing Vortex.');

      $output = '';
      $output .= PHP_EOL;
      $output .= 'Next steps:' . PHP_EOL;
      $output .= '  cd ' . $this->getDstDir() . PHP_EOL;
      $output .= '  git add -A                       # Add all files.' . PHP_EOL;
      $output .= '  git commit -m "Initial commit."  # Commit all files.' . PHP_EOL;
      $output .= '  ahoy build                       # Build site.' . PHP_EOL;
      $output .= PHP_EOL;
      $output .= '  See https://vortex.drevops.com/quickstart';
      $this->status($output, self::INSTALLER_STATUS_SUCCESS, TRUE, FALSE);
    }
  }

  protected function printTitle(string $text, string $fill = '-', int $width = 80, string $cols_delim = '|', bool $has_content = FALSE): void {
    $this->printDivider($fill, $width, 'down');
    $lines = explode(PHP_EOL, wordwrap($text, $width - 4, PHP_EOL));
    foreach ($lines as $line) {
      $line = ' ' . $line . ' ';
      print $cols_delim . str_pad($line, $width - 2, ' ', STR_PAD_BOTH) . $cols_delim . PHP_EOL;
    }
    $this->printDivider($fill, $width, $has_content ? 'up' : 'both');
  }

  protected function printSubtitle(string $text, string $fill = '=', int $width = 80): void {
    $is_multiline = strlen($text) + 4 >= $width;
    if ($is_multiline) {
      $this->printTitle($text, $fill, $width, 'both');
    }
    else {
      $text = ' ' . $text . ' ';
      print str_pad($text, $width, $fill, STR_PAD_BOTH) . PHP_EOL;
    }
  }

  protected function printDivider(string $fill = '-', int $width = 80, string $direction = 'none'): void {
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

    print $start . str_repeat($fill, $width - 2) . $finish . PHP_EOL;
  }

  protected function printBox(string $content, string $title = '', string $fill = '─', int $padding = 2, int $width = 80): void {
    $cols = '│';

    $max_width = $width - 2 - $padding * 2;
    $lines = explode(PHP_EOL, wordwrap(rtrim($content, PHP_EOL), $max_width, PHP_EOL));
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

  protected function printTick(?string $text = NULL): void {
    if (!empty($text) && $this->isInstallDebug()) {
      print PHP_EOL;
      $this->status($text, self::INSTALLER_STATUS_DEBUG, FALSE);
    }
    else {
      $this->status('.', self::INSTALLER_STATUS_MESSAGE, FALSE, FALSE);
    }
  }

  /**
   * Format values list.
   *
   * @param array<int|string, mixed> $values
   *   Array of values to format.
   * @param string $delim
   *   Delimiter to use.
   * @param int $width
   *   Width of the line.
   *
   * @return string
   *   Formatted values list.
   */
  protected function formatValuesList(array $values, string $delim = '', int $width = 80): string {
    // Only keep the keys that are not numeric.
    $keys = array_filter(array_keys($values), static fn($key): bool => !is_numeric($key));

    // Line width - length of delimiters * 2 - 2 spacers.
    $line_width = $width - strlen($delim) * 2 - 2;

    // Max name length + spaced on the sides + colon.
    $max_name_width = max(array_map(static fn(string $key): int => strlen($key), $keys)) + 2 + 1;

    // Whole width - (name width + 2 delimiters on the sides + 1 delimiter in
    // the middle + 2 spaces on the sides + 2 spaces for the center delimiter).
    $value_width = max($width - ($max_name_width + strlen($delim) * 2 + strlen($delim) + 2 + 2), 1);

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
        $lines = array_filter(explode(PHP_EOL, chunk_split(strval($value), $value_width, PHP_EOL)));
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

  protected function formatEnabled(mixed $value): string {
    return $value && strtolower((string) $value) !== 'n' ? 'Enabled' : 'Disabled';
  }

  protected function formatYesNo(string $value): string {
    return $value === self::ANSWER_YES ? 'Yes' : 'No';
  }

  protected function formatNotEmpty(mixed $value, mixed $default): mixed {
    return empty($value) ? $default : $value;
  }

  public static function fileContains(string $needle, string $filename): bool {
    if (!is_readable($filename)) {
      return FALSE;
    }

    $content = file_get_contents($filename);
    if (!$content) {
      return FALSE;
    }

    if (static::isRegex($needle)) {
      return (bool) preg_match($needle, $content);
    }

    return str_contains($content, $needle);
  }

  protected static function dirContains(string $needle, string $dir): bool {
    $files = static::scandirRecursive($dir, static::ignorePaths());
    foreach ($files as $filename) {
      if (static::fileContains($needle, $filename)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  protected static function isRegex(string $str): bool {
    if ($str === '' || strlen($str) < 3) {
      return FALSE;
    }

    return @preg_match($str, '') !== FALSE;
  }

  protected static function fileReplaceContent(string $needle, string $replacement, string $filename): void {
    if (!is_readable($filename) || static::fileIsExcludedFromProcessing($filename)) {
      return;
    }

    $content = file_get_contents($filename);
    if (!$content) {
      return;
    }

    if (static::isRegex($needle)) {
      $replaced = preg_replace($needle, $replacement, $content);
    }
    else {
      $replaced = str_replace($needle, $replacement, $content);
    }
    if ($replaced != $content) {
      file_put_contents($filename, $replaced);
    }
  }

  protected static function dirReplaceContent(string $needle, string $replacement, string $dir): void {
    $files = static::scandirRecursive($dir, static::ignorePaths());
    foreach ($files as $filename) {
      static::fileReplaceContent($needle, $replacement, $filename);
    }
  }

  protected function removeTokenWithContent(string $token, string $dir): void {
    $files = static::scandirRecursive($dir, static::ignorePaths());
    foreach ($files as $filename) {
      static::removeTokenFromFile($filename, '#;< ' . $token, '#;> ' . $token, TRUE);
    }
  }

  protected function removeTokenLine(string $token, string $dir): void {
    if (!empty($token)) {
      $files = static::scandirRecursive($dir, static::ignorePaths());
      foreach ($files as $filename) {
        static::removeTokenFromFile($filename, $token, NULL);
      }
    }
  }

  public static function removeTokenFromFile(string $filename, string $token_begin, ?string $token_end = NULL, bool $with_content = FALSE): void {
    if (self::fileIsExcludedFromProcessing($filename)) {
      return;
    }

    $token_end = $token_end ?? $token_begin;

    $content = file_get_contents($filename);
    if (!$content) {
      return;
    }

    if ($token_begin !== $token_end) {
      $token_begin_count = preg_match_all('/' . preg_quote($token_begin) . '/', $content);
      $token_end_count = preg_match_all('/' . preg_quote($token_end) . '/', $content);
      if ($token_begin_count !== $token_end_count) {
        throw new \RuntimeException(sprintf('Invalid begin and end token count in file %s: begin is %s(%s), end is %s(%s).', $filename, $token_begin, $token_begin_count, $token_end, $token_end_count));
      }
    }

    $out = [];
    $within_token = FALSE;

    $lines = file($filename);
    if (!$lines) {
      return;
    }

    foreach ($lines as $line) {
      if (str_contains($line, $token_begin)) {
        if ($with_content) {
          $within_token = TRUE;
        }
        continue;
      }
      elseif (str_contains($line, $token_end)) {
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

  protected static function replaceStringFilename(string $search, string $replace, string $dir): void {
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
   *
   * @param string $dir
   *   Directory to scan.
   * @param array<int, string> $ignore_paths
   *   Array of paths to ignore.
   * @param bool $include_dirs
   *   Include directories in the result.
   *
   * @return array<int, string>
   *   Array of discovered files.
   */
  protected static function scandirRecursive(string $dir, array $ignore_paths = [], bool $include_dirs = FALSE): array {
    $discovered = [];

    if (is_dir($dir)) {
      $files = scandir($dir);
      if (empty($files)) {
        return [];
      }

      $paths = array_diff($files, ['.', '..']);

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

  /**
   * Recursively scan directory for files.
   *
   * @param string $pattern
   *   Pattern to search.
   * @param int $flags
   *   Flags to pass to glob.
   *
   * @return array<int, string>
   *   Array of discovered files.
   */
  protected function globRecursive(string $pattern, int $flags = 0): array {
    $files = glob($pattern, $flags | GLOB_BRACE);

    if ($files) {
      $dirs = glob(dirname($pattern) . '/{,.}*[!.]', GLOB_BRACE | GLOB_ONLYDIR | GLOB_NOSORT);
      if ($dirs) {
        foreach ($dirs as $dir) {
          $files = array_merge($files, $this->globRecursive($dir . '/' . basename($pattern), $flags));
        }
      }
    }

    return $files ?: [];
  }

  /**
   * Get list of paths to ignore.
   *
   * @return array<int, string>
   *   Array of paths to ignore.
   */
  protected static function ignorePaths(): array {
    return array_merge([
      '/.git/',
      '/.idea/',
      '/vendor/',
      '/node_modules/',
      '/.data/',
    ], static::internalPaths());
  }

  /**
   * Get list of internal paths.
   *
   * @return array<int, string>
   *   Array of internal paths.
   */
  protected static function internalPaths(): array {
    return [
      '/LICENSE',
      '/CODE_OF_CONDUCT.md',
      '/CONTRIBUTING.md',
      '/LICENSE',
      '/SECURITY.md',
      '/.vortex/docs',
      '/.vortex/tests',
    ];
  }

  /**
   * Check if path is internal.
   *
   * @param string $path
   *   Path to check.
   *
   * @return bool
   *   TRUE if path is internal, FALSE otherwise.
   */
  protected static function isInternalPath(string $path): bool {
    $path = '/' . ltrim($path, './');

    return in_array($path, static::internalPaths());
  }

  /**
   * Check if file is excluded from processing.
   *
   * @param string $filename
   *   Filename to check.
   *
   * @return bool
   *   TRUE if file is excluded, FALSE otherwise.
   */
  protected static function fileIsExcludedFromProcessing(string $filename): bool {
    $excluded_patterns = [
      '.+\.png',
      '.+\.jpg',
      '.+\.jpeg',
      '.+\.bpm',
      '.+\.tiff',
    ];

    return (bool) preg_match('/^(' . implode('|', $excluded_patterns) . ')$/', $filename);
  }

  /**
   * Execute command.
   *
   * @param string $command
   *   Command to execute.
   * @param array<int, string>|null $output
   *   Output of the command.
   * @param int $return_var
   *   Return code of the command.
   *
   * @return string|false
   *   Result of the command.
   */
  protected function doExec(string $command, ?array &$output = NULL, ?int &$return_var = NULL): string|false {
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

  /**
   * Remove directory recursively.
   *
   * @param string $directory
   *   Directory to remove.
   * @param array<string,mixed> $options
   *   Options to pass.
   */
  protected static function rmdirRecursive(string $directory, array $options = []): void {
    if (!isset($options['traverseSymlinks'])) {
      $options['traverseSymlinks'] = FALSE;
    }

    $files = glob($directory . DIRECTORY_SEPARATOR . '{,.}*', GLOB_MARK | GLOB_BRACE);
    if (!empty($files)) {

      foreach ($files as $file) {
        if (basename($file) === '.' || basename($file) === '..') {
          continue;
        }

        if (substr($file, -1) === DIRECTORY_SEPARATOR) {
          if (!$options['traverseSymlinks'] && is_link(rtrim($file, DIRECTORY_SEPARATOR))) {
            unlink(rtrim($file, DIRECTORY_SEPARATOR));
          }
          else {
            static::rmdirRecursive($file, $options);
          }
        }
        else {
          unlink($file);
        }
      }
    }

    if (is_dir($directory = rtrim($directory, '\\/'))) {
      if (is_link($directory)) {
        unlink($directory);
      }
      else {
        rmdir($directory);
      }
    }
  }

  /**
   * Remove directory recursively if empty.
   *
   * @param string $directory
   *   Directory to remove.
   * @param array<string,mixed> $options
   *   Options to pass.
   */
  protected static function rmdirRecursiveEmpty(string $directory, array $options = []): void {
    if (static::dirIsEmpty($directory)) {
      static::rmdirRecursive($directory, $options);
      static::rmdirRecursiveEmpty(dirname($directory), $options);
    }
  }

  /**
   * Check if directory is empty.
   *
   * @param string $directory
   *   Directory to check.
   *
   * @return bool
   *   TRUE if directory is empty, FALSE otherwise.
   */
  protected static function dirIsEmpty(string $directory): bool {
    return is_dir($directory) && count(scandir($directory) ?: []) === 2;
  }

  protected function status(string $message, int $level = self::INSTALLER_STATUS_MESSAGE, bool $use_eol = TRUE, bool $use_prefix = TRUE): void {
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
      $this->out(($use_prefix ? $prefix . ' ' : '') . $message, $color, $use_eol);
    }
  }

  /**
   * Parse .env file.
   *
   * @param string $filename
   *   Filename to parse.
   *
   * @return array<string,string>
   *   Array of parsed values, key is the variable name.
   */
  protected static function parseDotenv(string $filename = '.env'): array {
    if (!is_readable($filename)) {
      return [];
    }

    $contents = file_get_contents($filename);
    if ($contents === FALSE) {
      return [];
    }

    // Replace all # not inside quotes.
    $contents = preg_replace('/#(?=(?:(?:[^"]*"){2})*[^"]*$)/', ';', $contents);

    return parse_ini_string($contents) ?: [];
  }

  /**
   * Load .env file.
   *
   * @param string $filename
   *   Filename to load.
   * @param bool $override_existing
   *   Override existing values.
   */
  protected static function loadDotenv(string $filename = '.env', bool $override_existing = FALSE): void {
    $values = static::parseDotenv($filename);

    foreach ($values as $var => $value) {
      if (!static::getenvOrDefault($var) || $override_existing) {
        putenv($var . '=' . $value);
      }
    }

    $GLOBALS['_ENV'] = $GLOBALS['_ENV'] ?? [];
    $GLOBALS['_SERVER'] = $GLOBALS['_SERVER'] ?? [];

    if ($override_existing) {
      $GLOBALS['_ENV'] = $values + $GLOBALS['_ENV'];
      $GLOBALS['_SERVER'] = $values + $GLOBALS['_SERVER'];
    }
    else {
      $GLOBALS['_ENV'] += $values;
      $GLOBALS['_SERVER'] += $values;
    }
  }

  /**
   * Reliable wrapper to work with environment values.
   */
  protected static function getenvOrDefault(string $name, mixed $default = NULL): mixed {
    $vars = getenv();

    if (!isset($vars[$name]) || $vars[$name] === '') {
      return $default;
    }

    return $vars[$name];
  }

  public static function tempdir(?string $dir = NULL, string $prefix = 'tmp_', int $mode = 0700, int $max_attempts = 1000): string {
    if (is_null($dir)) {
      $dir = sys_get_temp_dir();
    }

    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    if (!is_dir($dir) || !is_writable($dir)) {
      throw new \RuntimeException(sprintf('Temporary directory "%s" does not exist or is not writable.', $dir));
    }

    if (strpbrk($prefix, '\\/:*?"<>|') !== FALSE) {
      throw new \InvalidArgumentException('Invalid prefix.');
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

  protected function commandExists(string $command): void {
    $this->doExec('command -v ' . $command, $lines, $ret);
    if ($ret === 1) {
      throw new \RuntimeException(sprintf('Command "%s" does not exist in the current environment.', $command));
    }
  }

  protected static function toHumanName(string $value): ?string {
    $value = preg_replace('/[^a-zA-Z0-9]/', ' ', $value);
    $value = trim((string) $value);

    return preg_replace('/\s{2,}/', ' ', $value);
  }

  /**
   * Convert string to machine name.
   *
   * @param string $value
   *   Value to convert.
   * @param array<int|string> $preserve_chars
   *   Array of characters to preserve.
   *
   * @return string
   *   Converted value.
   */
  protected static function toMachineName(string $value, array $preserve_chars = []): string {
    $preserve = '';
    foreach ($preserve_chars as $char) {
      $preserve .= preg_quote(strval($char), '/');
    }
    $pattern = '/[^a-zA-Z0-9' . $preserve . ']/';

    $value = preg_replace($pattern, '_', $value);

    return strtolower($value);
  }

  protected static function toCamelCase(string $value, bool $capitalise_first = FALSE): string {
    $value = str_replace(' ', '', ucwords((string) preg_replace('/[^a-zA-Z0-9]/', ' ', $value)));

    return $capitalise_first ? $value : lcfirst($value);
  }

  protected function toAbbreviation(string $value, int $length = 2, string $word_delim = '_'): string {
    $value = trim($value);
    $value = str_replace(' ', '_', $value);
    $parts = empty($word_delim) ? [$value] : explode($word_delim, $value);

    if (count($parts) == 1) {
      return strlen($parts[0]) > $length ? substr($parts[0], 0, $length) : $value;
    }

    $value = implode('', array_map(static function (string $word): string {
      return substr($word, 0, 1);
    }, $parts));

    return substr($value, 0, $length);
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

    $name = $this->snakeToPascal($name);

    $callback = [static::class, $prefix . $name];
    if (method_exists($callback[0], $callback[1]) && is_callable($callback)) {
      return call_user_func_array($callback, $args);
    }

    return NULL;
  }

  protected function snakeToPascal(string $string): string {
    return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
  }

  /**
   * Get the value of a composer.json key.
   *
   * @param string $name
   *   Name of the key.
   *
   * @return mixed|null
   *   Value of the key or NULL if not found.
   */
  protected function getComposerJsonValue(string $name): mixed {
    $composer_json = $this->getDstDir() . DIRECTORY_SEPARATOR . 'composer.json';
    if (is_readable($composer_json)) {
      $contents = file_get_contents($composer_json);
      if ($contents === FALSE) {
        return NULL;
      }

      $json = json_decode($contents, TRUE);
      if (isset($json[$name])) {
        return $json[$name];
      }
    }

    return NULL;
  }

  protected function getStdinHandle(): mixed {
    global $_stdin_handle;

    if (!$_stdin_handle) {
      $h = fopen('php://stdin', 'r');
      if (!$h) {
        throw new \RuntimeException('Unable to open stdin handle.');
      }
      $_stdin_handle = stream_isatty($h) || static::getenvOrDefault('VORTEX_INSTALLER_FORCE_TTY') ? $h : fopen('/dev/tty', 'r+');
    }

    return $_stdin_handle;
  }

  protected function closeStdinHandle(): void {
    $_stdin_handle = $this->getStdinHandle();
    fclose($_stdin_handle);
  }

  protected function out(string $text, ?string $color = NULL, bool $new_line = TRUE): void {
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

  protected function debug(mixed $value, string $name = ''): void {
    print PHP_EOL;
    print trim($name . ' DEBUG START') . PHP_EOL;
    print print_r($value, TRUE) . PHP_EOL;
    print trim($name . ' DEBUG FINISH') . PHP_EOL;
    print PHP_EOL;
  }

}
