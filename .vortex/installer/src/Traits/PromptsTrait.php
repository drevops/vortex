<?php

declare(strict_types=1);

namespace DrevOps\Installer\Traits;

use DrevOps\Installer\Converter;
use DrevOps\Installer\File;

/**
 * Prompts trait.
 */
trait PromptsTrait {

  /**
   * Get default value router.
   */
  protected function getDefaultValue(string $name): mixed {
    // Allow to override default values from config variables.
    $config_name = strtoupper($name);

    return $this->config->get($config_name, $this->executeCallback('getDefaultValue', $name));
  }

  protected function getDefaultValueName(): ?string {
    return Converter::toHumanName(static::getenvOrDefault('VORTEX_PROJECT', basename((string) $this->config->getDstDir())));
  }

  protected function getDefaultValueMachineName(): ?string {
    return Converter::toMachineName($this->getAnswer('name', 'your_site'));
  }

  protected function getDefaultValueOrg(): string {
    return $this->getAnswer('name', 'Your Site') . ' Org';
  }

  protected function getDefaultValueOrgMachineName(): string {
    return Converter::toMachineName($this->getAnswer('org'));
  }

  protected function getDefaultValueModulePrefix(): string {
    return Converter::toAbbreviation($this->getAnswer('machine_name'));
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

  protected function processProfile(string $dir): void {
    $webroot = $this->getAnswer('webroot');
    // For core profiles - remove custom profile and direct links to it.
    if (in_array($this->getAnswer('profile'), $this->drupalCoreProfiles())) {
      File::rmdirRecursive(sprintf('%s/%s/profiles/your_site_profile', $dir, $webroot));
      File::rmdirRecursive(sprintf('%s/%s/profiles/custom/your_site_profile', $dir, $webroot));
      File::dirReplaceContent($webroot . '/profiles/your_site_profile,', '', $dir);
      File::dirReplaceContent($webroot . '/profiles/custom/your_site_profile,', '', $dir);
    }
    File::dirReplaceContent('your_site_profile', $this->getAnswer('profile'), $dir);
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

  protected function processProvisionUseProfile(string $dir): void {
    if ($this->getAnswer('provision_use_profile') === self::ANSWER_YES) {
      File::fileReplaceContent('/VORTEX_PROVISION_USE_PROFILE=.*/', "VORTEX_PROVISION_USE_PROFILE=1", $dir . '/.env');
      File::removeTokenWithContent('!PROVISION_USE_PROFILE', $dir);
    }
    else {
      File::fileReplaceContent('/VORTEX_PROVISION_USE_PROFILE=.*/', "VORTEX_PROVISION_USE_PROFILE=0", $dir . '/.env');
      File::removeTokenWithContent('PROVISION_USE_PROFILE', $dir);
    }
  }

  protected function processDatabaseDownloadSource(string $dir): void {
    $type = $this->getAnswer('database_download_source');
    File::fileReplaceContent('/VORTEX_DB_DOWNLOAD_SOURCE=.*/', 'VORTEX_DB_DOWNLOAD_SOURCE=' . $type, $dir . '/.env');

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
        File::removeTokenWithContent('!' . $token, $dir);
      }
      else {
        File::removeTokenWithContent($token, $dir);
      }
    }
  }

  protected function processDatabaseImage(string $dir): void {
    $image = $this->getAnswer('database_image');
    File::fileReplaceContent('/VORTEX_DB_IMAGE=.*/', 'VORTEX_DB_IMAGE=' . $image, $dir . '/.env');

    if ($image !== '' && $image !== '0') {
      File::removeTokenWithContent('!VORTEX_DB_IMAGE', $dir);
    }
    else {
      File::removeTokenWithContent('VORTEX_DB_IMAGE', $dir);
    }
  }

  protected function processOverrideExistingDb(string $dir): void {
    if ($this->getAnswer('override_existing_db') === self::ANSWER_YES) {
      File::fileReplaceContent('/VORTEX_PROVISION_OVERRIDE_DB=.*/', "VORTEX_PROVISION_OVERRIDE_DB=1", $dir . '/.env');
    }
    else {
      File::fileReplaceContent('/VORTEX_PROVISION_OVERRIDE_DB=.*/', "VORTEX_PROVISION_OVERRIDE_DB=0", $dir . '/.env');
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
      File::removeTokenWithContent('CI_PROVIDER_GHA', $dir);
    }

    if ($remove_circleci) {
      File::rmdirRecursive($dir . '/.circleci');
      @unlink($dir . '/tests/phpunit/CircleCiConfigTest.php');
      File::removeTokenWithContent('CI_PROVIDER_CIRCLECI', $dir);
    }

    if ($remove_gha && $remove_circleci) {
      @unlink($dir . '/docs/ci.md');
      File::removeTokenWithContent('CI_PROVIDER_ANY', $dir);
    }
    else {
      File::removeTokenWithContent('!CI_PROVIDER_ANY', $dir);
    }
  }

  protected function processDeployType(string $dir): void {
    $type = $this->getAnswer('deploy_type');
    if ($type !== 'none') {
      File::fileReplaceContent('/VORTEX_DEPLOY_TYPES=.*/', 'VORTEX_DEPLOY_TYPES=' . $type, $dir . '/.env');

      if (!str_contains($type, 'artifact')) {
        @unlink($dir . '/.gitignore.deployment');
        @unlink($dir . '/.gitignore.artifact');
      }

      File::removeTokenWithContent('!DEPLOYMENT', $dir);
    }
    else {
      @unlink($dir . '/docs/deployment.md');
      @unlink($dir . '/.gitignore.deployment');
      @unlink($dir . '/.gitignore.artifact');
      File::removeTokenWithContent('DEPLOYMENT', $dir);
    }
  }

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
        $db_file = static::getenvOrDefault('VORTEX_DB_DIR', './.data') . DIRECTORY_SEPARATOR . static::getenvOrDefault('VORTEX_DB_FILE', 'db.sql');
        $has_comment = File::fileContains('to allow to demonstrate how Vortex works without', $this->config->getDstDir() . '/.env');

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
    // Enable_commented_code.
    File::dirReplaceContent('##### ', '', $dir);
  }

  protected function processWebroot(string $dir): void {
    $new_name = $this->getAnswer('webroot', 'web');

    if ($new_name !== 'web') {
      File::dirReplaceContent('web/', $new_name . '/', $dir);
      File::dirReplaceContent('web\/', $new_name . '\/', $dir);
      File::dirReplaceContent(': web', ': ' . $new_name, $dir);
      File::dirReplaceContent('=web', '=' . $new_name, $dir);
      File::dirReplaceContent('!web', '!' . $new_name, $dir);
      File::dirReplaceContent('/\/web\//', '/' . $new_name . '/', $dir);
      File::dirReplaceContent('/\'\/web\'/', "'/" . $new_name . "'", $dir);
      rename($dir . DIRECTORY_SEPARATOR . 'web', $dir . DIRECTORY_SEPARATOR . $new_name);
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
      $this->config->getDstDir() . sprintf('/%s/modules/custom/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/sites/all/modules/custom/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/*/modules/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/*/modules/custom/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/modules/*_core', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/modules/custom/*_core', $webroot),
    ];

    $path = File::findMatchingPath($locations);

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
      $this->config->getDstDir() . sprintf('/%s/profiles/*/*.info', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/*/*.info.yml', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/*.info', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/*.info.yml', $webroot),
    ];

    $name = File::findMatchingPath($locations, 'Drupal 10 profile implementation of');

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
      $this->config->getDstDir() . sprintf('/%s/themes/custom/*/*.info', $webroot),
      $this->config->getDstDir() . sprintf('/%s/themes/custom/*/*.info.yml', $webroot),
      $this->config->getDstDir() . sprintf('/%s/sites/all/themes/custom/*/*.info', $webroot),
      $this->config->getDstDir() . sprintf('/%s/sites/all/themes/custom/*/*.info.yml', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/*/themes/custom/*/*.info', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/*/themes/custom/*/*.info.yml', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/themes/custom/*/*.info', $webroot),
      $this->config->getDstDir() . sprintf('/%s/profiles/custom/*/themes/custom/*/*.info.yml', $webroot),
    ];

    $name = File::findMatchingPath($locations);

    if (empty($name)) {
      return NULL;
    }

    $name = basename($name);

    return str_replace(['.info.yml', '.info'], '', $name);
  }

  protected function discoverValueUrl(): ?string {
    $webroot = $this->getAnswer('webroot');

    $origin = NULL;
    $path = $this->config->getDstDir() . sprintf('/%s/sites/default/settings.php', $webroot);

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
    if (is_readable($this->config->getDstDir() . '/.github/workflows/build-test-deploy.yml')) {
      return 'GitHub Actions';
    }

    if (is_readable($this->config->getDstDir() . '/.circleci/config.yml')) {
      return 'CircleCI';
    }

    return $this->isInstalled() ? 'none' : NULL;
  }

  protected function discoverValueDeployType(): ?string {
    return $this->getValueFromDstDotenv('VORTEX_DEPLOY_TYPES');
  }

  protected function discoverValuePreserveAcquia(): ?string {
    if (is_readable($this->config->getDstDir() . '/hooks')) {
      return self::ANSWER_YES;
    }

    $value = $this->getValueFromDstDotenv('VORTEX_DB_DOWNLOAD_SOURCE');

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

    return is_readable($this->config->getDstDir() . '/renovate.json') ? self::ANSWER_YES : self::ANSWER_NO;
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

  protected function normaliseAnswerName(string $value): string {
    return ucfirst((string) Converter::toHumanName($value));
  }

  protected function normaliseAnswerMachineName(string $value): string {
    return Converter::toMachineName($value);
  }

  protected function normaliseAnswerOrgMachineName(string $value): string {
    return Converter::toMachineName($value);
  }

  protected function normaliseAnswerModulePrefix(string $value): string {
    return Converter::toMachineName($value);
  }

  protected function normaliseAnswerProfile(string $value): string {
    $profile = Converter::toMachineName($value);

    if (empty($profile) || strtolower($profile) === self::ANSWER_NO) {
      $profile = 'standard';
    }

    return $profile;
  }

  protected function normaliseAnswerTheme(string $value): string {
    return Converter::toMachineName($value);
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
    $value = Converter::toMachineName($value, ['-', '/', ':', '.']);

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
   * Normalisation router.
   */
  protected function normaliseAnswer(string $name, mixed $value): mixed {
    $normalised = $this->executeCallback('normaliseAnswer', $name, strval($value));

    return $normalised ?? $value;
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
    $composer_json = $this->config->getDstDir() . DIRECTORY_SEPARATOR . 'composer.json';
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
