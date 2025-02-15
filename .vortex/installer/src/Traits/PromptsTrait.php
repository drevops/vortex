<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

use DrevOps\Installer\Utils\Callback;
use DrevOps\Installer\Utils\Converter;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\File;

/**
 * Prompts trait.
 */
trait PromptsTrait {










  protected function processPreserveAcquia(string $dir): void {
    if ($this->getAnswer('preserve_acquia') === self::ANSWER_YES) {
      File::removeTokenWithContent('!ACQUIA', $dir);
    }
    else {
      File::rmdirRecursive($dir . '/hooks');
      $webroot = $this->getAnswer('webroot');
      @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.acquia.php', $dir, $webroot));
      File::removeTokenWithContent('ACQUIA', $dir);
    }
  }

  protected function processPreserveLagoon(string $dir): void {
    if ($this->getAnswer('preserve_lagoon') === self::ANSWER_YES) {
      File::removeTokenWithContent('!LAGOON', $dir);
    }
    else {
      @unlink($dir . '/drush/sites/lagoon.site.yml');
      @unlink($dir . '/.lagoon.yml');
      @unlink($dir . '/.github/workflows/close-pull-request.yml');
      $webroot = $this->getAnswer('webroot');
      @unlink(sprintf('%s/%s/sites/default/includes/providers/settings.lagoon.php', $dir, $webroot));
      File::removeTokenWithContent('LAGOON', $dir);
    }
  }

  protected function processPreserveFtp(string $dir): void {
    if ($this->getAnswer('preserve_ftp') === self::ANSWER_YES) {
      File::removeTokenWithContent('!FTP', $dir);
    }
    else {
      File::removeTokenWithContent('FTP', $dir);
    }
  }

  protected function processPreserveRenovatebot(string $dir): void {
    if ($this->getAnswer('preserve_renovatebot') === self::ANSWER_YES) {
      File::removeTokenWithContent('!RENOVATEBOT', $dir);
    }
    else {
      @unlink($dir . '/renovate.json');
      File::removeTokenWithContent('RENOVATEBOT', $dir);
    }
  }

  protected function processDemoMode(string $dir): void {
    // Only discover demo mode if not explicitly set.
    if (is_null($this->config->get('VORTEX_INSTALL_DEMO'))) {
      if ($this->getAnswer('provision_use_profile') === self::ANSWER_NO) {
        $download_source = $this->getAnswer('database_download_source');
        $db_file = Env::get('VORTEX_DB_DIR', './.data') . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_FILE', 'db.sql');
        $has_comment = File::fileContains('Override project-specific values for demonstration purposes', $this->config->getDstDir() . '/.env');

        // Enable Vortex demo mode if download source is file AND
        // there is no downloaded file present OR if there is a demo comment in
        // destination .env file.
        if ($download_source !== 'container_registry') {
          if ($has_comment || !file_exists($db_file)) {
            $this->config->set('VORTEX_INSTALL_DEMO', TRUE);
          }
          else {
            $this->config->set('VORTEX_INSTALL_DEMO', FALSE);
          }
        }
        elseif ($has_comment) {
          $this->config->set('VORTEX_INSTALL_DEMO', TRUE);
        }
        else {
          $this->config->set('VORTEX_INSTALL_DEMO', FALSE);
        }
      }
      else {
        $this->config->set('VORTEX_INSTALL_DEMO', FALSE);
      }
    }

    if (!$this->config->get('VORTEX_INSTALL_DEMO')) {
      File::removeTokenWithContent('DEMO', $dir);
    }
  }

  protected function processPreserveVortexInfo(string $dir): void {
    if ($this->getAnswer('preserve_vortex_info') === self::ANSWER_NO) {
      // Remove code required for Vortex maintenance.
      File::removeTokenWithContent('VORTEX_DEV', $dir);

      // Remove all other comments.
      File::removeTokenLine('#;', $dir);
    }
  }

  protected function processVortexInternal(string $dir): void {
    if (file_exists($dir . DIRECTORY_SEPARATOR . 'README.dist.md')) {
      rename($dir . DIRECTORY_SEPARATOR . 'README.dist.md', $dir . DIRECTORY_SEPARATOR . 'README.md');
    }

    // Remove Vortex internal files.
    File::rmdirRecursive($dir . DIRECTORY_SEPARATOR . '.vortex');

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
    File::removeTokenLine('#;<', $dir);
    File::removeTokenLine('#;>', $dir);
  }

  protected function processEnableCommentedCode(string $dir): void {
    File::dirReplaceContent('##### ', '', $dir);
  }

  protected function processEmptyLines(string $dir): void {
    $ignore = array_merge(File::ignorePaths(), [
      '/web/sites/default/default.settings.php',
      '/web/sites/default/default.services.yml',
      '/.docker/config/solr/config-set/',
    ]);

    $files = File::scandirRecursive($dir, $ignore);
    foreach ($files as $filename) {
      File::fileReplaceContent('/(\n\s*\n)+/', "\n\n", $filename);
    }
  }



  protected function processPreserveOnboarding(string $dir): void {
    if ($this->getAnswer('preserve_onboarding') !== self::ANSWER_YES) {
      @unlink($dir . '/docs/onboarding.md');
      File::removeTokenWithContent('ONBOARDING', $dir);
    }
  }

  protected function processPreserveDocComments(string $dir): void {
    if ($this->getAnswer('preserve_doc_comments') === self::ANSWER_YES) {
      // Replace special "#: " comments with normal "#" comments.
      File::dirReplaceContent('#:', '#', $dir);
    }
    else {
      File::removeTokenLine('#:', $dir);
    }
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
    $value = Callback::execute('discoverValue', $name);

    return is_null($value) ? $this->getDefaultValue($name) : $value;
  }


  protected function discoverValuePreserveAcquia(): ?string {
    if (is_readable($this->config->getDstDir() . '/hooks')) {
      return self::ANSWER_YES;
    }

    $value = Env::getFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');

    if (is_null($value)) {
      return NULL;
    }

    return $value == 'acquia' ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveLagoon(): ?string {
    if (is_readable($this->config->getDstDir() . '/.lagoon.yml')) {
      return self::ANSWER_YES;
    }

    if ($this->getAnswer('deploy_type') === 'lagoon') {
      return self::ANSWER_YES;
    }

    $value = Env::getFromDstDotenv('LAGOON_PROJECT');

    // Special case - only work with non-empty value as 'LAGOON_PROJECT'
    // may not exist in installed site's .env file.
    if (empty($value)) {
      return NULL;
    }

    return self::ANSWER_YES;
  }

  protected function discoverValuePreserveFtp(): ?string {
    $value = Env::getFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');
    if (is_null($value)) {
      return NULL;
    }

    return $value == 'ftp' ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveRenovatebot(): ?string {
    if (!$this->isInstalled()) {
      return NULL;
    }

    return is_readable($this->config->getDstDir() . '/renovate.json') ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveOnboarding(): ?string {
    if ($this->isInstalled()) {
      $file = $this->config->getDstDir() . '/docs/onboarding.md';

      return is_readable($file) ? self::ANSWER_YES : self::ANSWER_NO;
    }

    return NULL;
  }

  protected function discoverValuePreserveDocComments(): ?string {
    $file = $this->config->getDstDir() . '/.ahoy.yml';

    if (!is_readable($file)) {
      return NULL;
    }

    return File::fileContains('Ahoy configuration file', $file) ? self::ANSWER_YES : self::ANSWER_NO;
  }

  protected function discoverValuePreserveVortexInfo(): ?string {
    $file = $this->config->getDstDir() . '/.ahoy.yml';
    if (!is_readable($file)) {
      return NULL;
    }

    return File::fileContains('Comments starting with', $file) ? self::ANSWER_YES : self::ANSWER_NO;
  }

  /**
   * Check that Vortex is installed for this project.
   */
  protected function isInstalled(): bool {
    $path = $this->config->getDstDir() . DIRECTORY_SEPARATOR . 'README.md';

    if (!file_exists($path)) {
      return FALSE;
    }

    $content = file_get_contents($path);
    if (!$content) {
      return FALSE;
    }

    return (bool) preg_match('/badge\/Vortex\-/', $content);
  }

  protected function processStringTokens(string $dir): void {
    $machine_name_kebab = Converter::kebab($this->getAnswer('machine_name'));
    $machine_name_pascal = Converter::pascal($this->getAnswer('machine_name'));
    $module_prefix_pascal = Converter::pascal($this->getAnswer('module_prefix'));
    $module_prefix_cobol = Converter::cobol($module_prefix_pascal);
    $theme_pascal = Converter::pascal($this->getAnswer('theme'));
    $vortex_version_urlencoded = str_replace('-', '--', (string) $this->config->get('VORTEX_VERSION'));
    $webroot = $this->getAnswer('webroot');

    // @formatter:off
    // phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:disable Drupal.WhiteSpace.Comma.TooManySpaces
    File::dirReplaceContent('your_site_theme',          $this->getAnswer('theme'),                     $dir);
    File::dirReplaceContent('YourSiteTheme',            $theme_pascal,                            $dir);
    File::dirReplaceContent('your_org',                 $this->getAnswer('org_machine_name'),          $dir);
    File::dirReplaceContent('YOURORG',                  $this->getAnswer('org'),                       $dir);
    File::dirReplaceContent('your-site-domain.example', $this->getAnswer('domain'),                    $dir);
    File::dirReplaceContent('ys_core',                  $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('ys_search',                $this->getAnswer('module_prefix') . '_search', $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('ys_core',                  $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/themes/custom',  $webroot));
    File::dirReplaceContent('ys_core',                  $this->getAnswer('module_prefix') . '_core',   $dir . '/scripts/custom');
    File::dirReplaceContent('ys_search',                $this->getAnswer('module_prefix') . '_search', $dir . '/scripts/custom');
    File::dirReplaceContent('YsCore',                   $module_prefix_pascal . 'Core',           $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('YsSearch',                 $module_prefix_pascal . 'Search',         $dir . sprintf('/%s/modules/custom', $webroot));
    File::dirReplaceContent('YSCODE',                   $module_prefix_cobol,                      $dir);
    File::dirReplaceContent('YSSEARCH',                 $module_prefix_cobol,                      $dir);
    File::dirReplaceContent('your-site',                $machine_name_kebab,                      $dir);
    File::dirReplaceContent('your_site',                $this->getAnswer('machine_name'),              $dir);
    File::dirReplaceContent('YOURSITE',                 $this->getAnswer('name'),                      $dir);
    File::dirReplaceContent('YourSite',                 $machine_name_pascal,                     $dir);

    File::replaceStringFilename('YourSiteTheme',        $theme_pascal,                            $dir);
    File::replaceStringFilename('your_site_theme',      $this->getAnswer('theme'),                     $dir);
    File::replaceStringFilename('YourSite',             $machine_name_pascal,                     $dir);
    File::replaceStringFilename('ys_core',              $this->getAnswer('module_prefix') . '_core',   $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('ys_search',            $this->getAnswer('module_prefix') . '_search', $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('YsCore',               $module_prefix_pascal . 'Core',           $dir . sprintf('/%s/modules/custom', $webroot));
    File::replaceStringFilename('your_org',             $this->getAnswer('org_machine_name'),          $dir);
    File::replaceStringFilename('your_site',            $this->getAnswer('machine_name'),              $dir);

    File::dirReplaceContent('VORTEX_VERSION_URLENCODED', $vortex_version_urlencoded,                $dir);
    File::dirReplaceContent('VORTEX_VERSION',            $this->config->get('VORTEX_VERSION'),        $dir);
    // @formatter:on
    // phpcs:enable Generic.Functions.FunctionCallArgumentSpacing.TooMuchSpaceAfterComma
    // phpcs:enable Drupal.WhiteSpace.Comma.TooManySpaces
  }

}
