<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\File\Tests\Traits\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Tests\Traits\FileAssertionsTrait;
use PHPUnit\Framework\Assert;

/**
 * Provides file and directory assertion methods.
 */
trait AssertFilesTrait {

  use DirectoryAssertionsTrait;
  use FileAssertionsTrait;

  protected function assertCommonFilesPresent(string $webroot = 'web', string $project_name = 'star_wars'): void {
    $this->assertVortexFilesPresent($webroot);

    // Assert that project name is correct.
    Assert::assertFileExists('.env');
    $this->assertFileContainsString('VORTEX_PROJECT=' . $project_name, '.env');

    // Assert that Vortex version was replaced in README.md.
    Assert::assertFileExists('README.md');
    $vortex_version = getenv('TEST_VORTEX_VERSION') ?: 'develop';
    $this->assertFileContainsString(sprintf('badge/Vortex-%s-65ACBC.svg', $vortex_version), 'README.md');
    $this->assertFileContainsString('https://github.com/drevops/vortex/tree/' . $vortex_version, 'README.md');
    $this->assertFileNotContainsString('The following list includes', 'README.md');
    Assert::assertFileDoesNotExist('README.dist.md');

    $this->assertFileContainsString('This repository was created using the [Vortex](https://github.com/drevops/vortex) project template', 'README.md', 'Assert that Vortex footnote remains.');

    // Assert Drupal files are present.
    $this->assertDrupalFilesPresent($webroot);
  }

  protected function assertCommonFilesAbsent(string $webroot = 'web'): void {
    // Example directories and files that should not exist after Vortex removed.
    Assert::assertDirectoryDoesNotExist($webroot . '/profiles/custom/your_site_profile');
    Assert::assertDirectoryDoesNotExist($webroot . '/modules/custom/ys_base');
    Assert::assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Unit/YourSiteExampleUnitTest.php');
    Assert::assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Unit/YourSiteCoreUnitTestBase.php');
    Assert::assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Kernel/YourSiteExampleKernelTest.php');
    Assert::assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Kernel/YourSiteCoreKernelTestBase.php');
    Assert::assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Functional/YourSiteExampleFunctionalTest.php');
    Assert::assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Functional/YourSiteCoreFunctionalTestBase.php');
    Assert::assertDirectoryDoesNotExist($webroot . '/modules/custom/ys_search');
    Assert::assertDirectoryDoesNotExist($webroot . '/themes/custom/your_site_theme');

    // Example settings files that should not exist.
    Assert::assertFileDoesNotExist($webroot . '/sites/default/default.settings.local.php');
    Assert::assertFileDoesNotExist($webroot . '/sites/default/default.services.local.yml');

    // Documentation and CI files that should not exist in some contexts.
    Assert::assertFileDoesNotExist('docs/faqs.md');
    Assert::assertFileDoesNotExist('.ahoy.yml');
    Assert::assertFileDoesNotExist('README.md');
    Assert::assertFileDoesNotExist('.github/workflows/build-test-deploy.yml');
    Assert::assertFileDoesNotExist('.circleci/config.yml');

    // Core Drupal files that should not exist in webroot after Vortex removal.
    Assert::assertFileDoesNotExist($webroot . '/sites/default/settings.php');
    Assert::assertFileDoesNotExist($webroot . '/sites/default/services.yml');

    // Drupal Scaffold files that should not exist in some contexts.
    Assert::assertFileDoesNotExist($webroot . '/.editorconfig');
    Assert::assertFileDoesNotExist($webroot . '/.eslintignore');
    Assert::assertFileDoesNotExist($webroot . '/.gitattributes');
    Assert::assertFileDoesNotExist($webroot . '/.htaccess');
    Assert::assertFileDoesNotExist($webroot . '/autoload.php');
    Assert::assertFileDoesNotExist($webroot . '/index.php');
    Assert::assertFileDoesNotExist($webroot . '/robots.txt');
    Assert::assertFileDoesNotExist($webroot . '/update.php');
  }

  protected function assertVortexFilesPresent(string $webroot = 'web'): void {
    // Docker configuration files.
    Assert::assertFileExists('.docker/cli.dockerfile');
    Assert::assertFileExists('.docker/database.dockerfile');
    Assert::assertFileExists('.docker/nginx-drupal.dockerfile');
    Assert::assertFileExists('.docker/php.dockerfile');
    Assert::assertFileExists('.docker/solr.dockerfile');
    Assert::assertFileExists('.docker/scripts/.gitkeep');
    Assert::assertFileExists('.docker/config/database/my.cnf');

    // Solr configuration files.
    Assert::assertFileExists('.docker/config/solr/config-set/accents_en.txt');
    Assert::assertFileExists('.docker/config/solr/config-set/accents_und.txt');
    Assert::assertFileExists('.docker/config/solr/config-set/elevate.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/protwords_en.txt');
    Assert::assertFileExists('.docker/config/solr/config-set/protwords_und.txt');
    Assert::assertFileExists('.docker/config/solr/config-set/schema.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/schema_extra_fields.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/schema_extra_types.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/solrconfig.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/solrconfig_extra.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/solrconfig_index.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/solrconfig_query.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/solrconfig_requestdispatcher.xml');
    Assert::assertFileExists('.docker/config/solr/config-set/solrcore.properties');
    Assert::assertFileExists('.docker/config/solr/config-set/stopwords_en.txt');
    Assert::assertFileExists('.docker/config/solr/config-set/stopwords_und.txt');
    Assert::assertFileExists('.docker/config/solr/config-set/synonyms_en.txt');
    Assert::assertFileExists('.docker/config/solr/config-set/synonyms_und.txt');

    // GitHub files.
    Assert::assertFileExists('.github/PULL_REQUEST_TEMPLATE.md');

    // Configuration directories.
    Assert::assertDirectoryExists('config/ci');
    Assert::assertDirectoryExists('config/default');
    Assert::assertDirectoryExists('config/dev');
    Assert::assertDirectoryExists('config/local');
    Assert::assertDirectoryExists('config/stage');

    // Patches directory.
    Assert::assertFileExists('patches/.gitkeep');

    // Script files.
    Assert::assertFileExists('scripts/composer/ScriptHandler.php');
    Assert::assertFileExists('scripts/custom/.gitkeep');

    // Core Vortex files.
    Assert::assertFileExists('scripts/vortex/deploy.sh');
    Assert::assertFileExists('scripts/vortex/deploy-artifact.sh');
    Assert::assertFileExists('scripts/vortex/deploy-container-registry.sh');
    Assert::assertFileExists('scripts/vortex/deploy-lagoon.sh');
    Assert::assertFileExists('scripts/vortex/deploy-webhook.sh');
    Assert::assertFileExists('scripts/vortex/login-container-registry.sh');
    Assert::assertFileExists('scripts/vortex/doctor.sh');
    Assert::assertFileExists('scripts/vortex/download-db.sh');
    Assert::assertFileExists('scripts/vortex/download-db-acquia.sh');
    Assert::assertFileExists('scripts/vortex/download-db-url.sh');
    Assert::assertFileExists('scripts/vortex/download-db-ftp.sh');
    Assert::assertFileExists('scripts/vortex/download-db-container-registry.sh');
    Assert::assertFileExists('scripts/vortex/download-db-lagoon.sh');
    Assert::assertFileExists('scripts/vortex/export-db-file.sh');
    Assert::assertFileExists('scripts/vortex/export-db-image.sh');
    Assert::assertFileExists('scripts/vortex/provision.sh');
    Assert::assertFileExists('scripts/vortex/login.sh');
    Assert::assertFileExists('scripts/vortex/login-container-registry.sh');
    Assert::assertFileExists('scripts/vortex/provision-sanitize-db.sh');
    Assert::assertFileExists('scripts/vortex/info.sh');
    Assert::assertFileExists('scripts/vortex/notify.sh');
    Assert::assertFileExists('scripts/vortex/notify-email.sh');
    Assert::assertFileExists('scripts/vortex/notify-github.sh');
    Assert::assertFileExists('scripts/vortex/notify-jira.sh');
    Assert::assertFileExists('scripts/vortex/notify-newrelic.sh');
    Assert::assertFileExists('scripts/vortex/reset.sh');
    Assert::assertFileExists('scripts/vortex/task-copy-db-acquia.sh');
    Assert::assertFileExists('scripts/vortex/task-copy-files-acquia.sh');
    Assert::assertFileExists('scripts/vortex/task-purge-cache-acquia.sh');
    Assert::assertFileExists('scripts/vortex/update-vortex.sh');

    Assert::assertFileExists('scripts/sanitize.sql');

    // Test files.
    Assert::assertFileExists('tests/behat/bootstrap/FeatureContext.php');
    Assert::assertDirectoryExists('tests/behat/features');

    // Root configuration files.
    Assert::assertFileExists('.ahoy.yml');
    Assert::assertFileExists('.dockerignore');
    Assert::assertFileExists('.editorconfig');
    Assert::assertFileExists('.env');
    Assert::assertFileDoesNotExist('.gitattributes');
    Assert::assertFileExists('.ahoy.local.example.yml');
    Assert::assertFileExists('.env.local.example');
    Assert::assertFileExists('.gitignore');
    Assert::assertFileExists('behat.yml');
    Assert::assertFileExists('composer.json');
    Assert::assertFileExists('docker-compose.yml');
    Assert::assertFileExists('gherkinlint.json');
    Assert::assertFileExists('phpcs.xml');
    Assert::assertFileExists('phpstan.neon');
    Assert::assertFileExists('phpunit.xml');

    // Documentation files.
    Assert::assertFileExists('docs/faqs.md');
    Assert::assertFileExists('README.md');
    Assert::assertFileExists('docs/releasing.md');
    Assert::assertFileExists('docs/testing.md');

    // Assert that Vortex files removed.
    Assert::assertDirectoryDoesNotExist('.vortex');
    Assert::assertFileDoesNotExist('LICENSE');
    Assert::assertFileDoesNotExist('CODE_OF_CONDUCT.md');
    Assert::assertFileDoesNotExist('.github/FUNDING.yml');

    Assert::assertFileExists('.github/workflows/assign-author.yml');
    Assert::assertFileExists('.github/workflows/label-merge-conflict.yml');
    Assert::assertFileExists('.github/workflows/draft-release-notes.yml');

    Assert::assertFileDoesNotExist('.github/workflows/vortex-release-docs.yml');
    Assert::assertFileDoesNotExist('.github/workflows/vortex-test-docs.yml');
    Assert::assertFileDoesNotExist('.github/workflows/vortex-test-common.yml');
    Assert::assertFileDoesNotExist('.github/workflows/vortex-test-installer.yml');

    if (file_exists('.circleci/config.yml')) {
      $this->assertFileNotContainsString('vortex-dev', '.circleci/config.yml', 'CircleCI config should not contain development Vortex references');
    }

    // Assert that documentation was processed correctly.
    if (file_exists('README.md')) {
      $this->assertFileNotContainsString('# Vortex', 'README.md');
    }

    // Check directory doesn't contain .vortex references.
    $this->assertDirectoryNotContainsString('.', '/\.vortex');
  }

  protected function assertDrupalFilesPresent(string $webroot = 'web'): void {
    // Stub profile removed.
    Assert::assertDirectoryDoesNotExist($webroot . '/profiles/custom/your_site_profile');
    // Stub code module removed.
    Assert::assertDirectoryDoesNotExist($webroot . '/modules/custom/ys_base');
    // Stub theme removed.
    Assert::assertDirectoryDoesNotExist($webroot . '/themes/custom/your_site_theme');

    // Site core module created.
    Assert::assertDirectoryExists($webroot . '/modules/custom/sw_base');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/sw_base.info.yml');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/sw_base.module');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/sw_base.deploy.php');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Unit/SwBaseUnitTestBase.php');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Unit/ExampleTest.php');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Kernel/SwBaseKernelTestBase.php');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Kernel/ExampleTest.php');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Functional/SwBaseFunctionalTestBase.php');
    Assert::assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Functional/ExampleTest.php');

    // Site search module created.
    Assert::assertDirectoryExists($webroot . '/modules/custom/sw_search');
    Assert::assertFileExists($webroot . '/modules/custom/sw_search/sw_search.info.yml');

    // Site theme created.
    Assert::assertDirectoryExists($webroot . '/themes/custom/star_wars');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/js/star_wars.js');
    Assert::assertDirectoryExists($webroot . '/themes/custom/star_wars/scss');
    Assert::assertDirectoryExists($webroot . '/themes/custom/star_wars/images');
    Assert::assertDirectoryExists($webroot . '/themes/custom/star_wars/fonts');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/.gitignore');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/star_wars.info.yml');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/star_wars.libraries.yml');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/star_wars.theme');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/Gruntfile.js');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/package.json');

    Assert::assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Unit/StarWarsUnitTestBase.php');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Unit/ExampleTest.php');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Kernel/StarWarsKernelTestBase.php');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Kernel/ExampleTest.php');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Functional/StarWarsFunctionalTestBase.php');
    Assert::assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Functional/ExampleTest.php');

    // Drupal Scaffold files exist.
    Assert::assertFileDoesNotExist($webroot . '/.editorconfig');
    Assert::assertFileDoesNotExist($webroot . '/.eslintignore');
    Assert::assertFileDoesNotExist($webroot . '/.eslintrc.json');
    Assert::assertFileDoesNotExist($webroot . '/.gitattributes');
    Assert::assertFileExists($webroot . '/.htaccess');
    Assert::assertFileExists($webroot . '/autoload.php');
    Assert::assertFileExists($webroot . '/index.php');
    Assert::assertFileExists($webroot . '/robots.txt');
    Assert::assertFileExists($webroot . '/update.php');

    // Settings files exist.
    Assert::assertFileExists($webroot . '/sites/default/settings.php');
    Assert::assertDirectoryExists($webroot . '/sites/default/includes/');
    Assert::assertFileExists($webroot . '/sites/default/default.settings.php');
    Assert::assertFileExists($webroot . '/sites/default/default.services.yml');
    Assert::assertFileExists($webroot . '/sites/default/default.settings.local.php');
    Assert::assertFileExists($webroot . '/sites/default/default.services.local.yml');

    // Assert all stub strings were replaced - these should not exist in any
    // files.
    $this->assertDirectoryNotContainsString('.', 'your_site');
    $this->assertDirectoryNotContainsString('.', 'ys_base');
    $this->assertDirectoryNotContainsString('.', 'YOURSITE');
    $this->assertDirectoryNotContainsString('.', 'YourSite');
    $this->assertDirectoryNotContainsString('.', 'your_site_theme');
    $this->assertDirectoryNotContainsString('.', 'your_org');
    $this->assertDirectoryNotContainsString('.', 'YOURORG');
    $this->assertDirectoryNotContainsString('.', 'www.your-site-domain.example');
    // Assert all special comments were removed.
    $this->assertDirectoryNotContainsString('.', '#;');
    $this->assertDirectoryNotContainsString('.', '#;<');
    $this->assertDirectoryNotContainsString('.', '#;>');
  }

  public function assertFilesTrackedInGit(string $webroot = 'web', bool $skip_commit = FALSE): void {
    $this->createDevelopmentDrupalSettings($webroot);

    if (!$skip_commit) {
      $this->gitCommitAll('.', 'Commit fully built project');
    }

    // Assert that Drupal Scaffold files were added to the git repository.
    $this->gitAssertFilesTracked($webroot . '/.htaccess');
    $this->gitAssertFilesTracked($webroot . '/autoload.php');
    $this->gitAssertFilesTracked($webroot . '/index.php');
    $this->gitAssertFilesTracked($webroot . '/robots.txt');
    $this->gitAssertFilesTracked($webroot . '/update.php');

    // Assert that lock files were added to the git repository.
    $this->gitAssertFilesTracked('composer.lock');
    $this->gitAssertFilesTracked($webroot . '/themes/custom/star_wars/yarn.lock');

    $this->gitAssertFilesNotTracked('.data/db.sql');

    // Assert that local settings were not added to the git repository.
    $this->gitAssertFilesNotTracked($webroot . '/sites/default/settings.local.php');
    $this->gitAssertFilesNotTracked($webroot . '/sites/default/services.local.yml');
    $this->gitAssertFilesNotTracked('docker-compose.override.yml');

    // Assert that built assets were not added to the git repository.
    $this->gitAssertFilesNotTracked($webroot . '/themes/custom/star_wars/build/css/star_wars.min.css');
    $this->gitAssertFilesNotTracked($webroot . '/themes/custom/star_wars/build/js/star_wars.js');

    $this->removeDevelopmentDrupalSettings($webroot);
  }

  protected function createDevelopmentDrupalSettings(string $webroot = 'web'): void {
    Assert::assertFileExists($webroot . '/sites/default/default.settings.local.php');
    Assert::assertFileExists($webroot . '/sites/default/default.services.local.yml');

    File::copy($webroot . '/sites/default/default.settings.local.php', $webroot . '/sites/default/settings.local.php');
    File::copy($webroot . '/sites/default/default.services.local.yml', $webroot . '/sites/default/services.local.yml');

    Assert::assertFileExists($webroot . '/sites/default/settings.local.php');
    Assert::assertFileExists($webroot . '/sites/default/services.local.yml');
  }

  protected function removeDevelopmentDrupalSettings(string $webroot = 'web'): void {
    File::remove([
      $webroot . '/sites/default/default.settings.local.php',
      $webroot . '/sites/default/default.services.local.yml',
    ]);
  }

}
