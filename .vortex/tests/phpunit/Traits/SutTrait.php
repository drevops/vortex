<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\File\Tests\Traits\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Tests\Traits\FileAssertionsTrait;

/**
 * Provides system under test preparations.
 */
trait SutTrait {

  use DirectoryAssertionsTrait;
  use FileAssertionsTrait;

  /**
   * URL to the test demo database.
   *
   * Tests use demo database and 'ahoy download-db' command, so we need
   * to set the CURL DB to test DB.
   */
  const VORTEX_INSTALLER_DEMO_DB_TEST = 'https://github.com/drevops/vortex/releases/download/25.4.0/db_d11_2.test.sql';

  /**
   * Environment variables to set when running the installer.
   *
   * @var array <string, string>
   */
  protected static $sutInstallerEnv = [];

  protected function prepareSut(): void {
    $this->logStepStart();

    $this->logSubstep('Prepare global gitconfig');
    $this->prepareGlobalGitconfig();

    $this->logSubstep('Prepare global gitignore');
    $this->prepareGlobalGitignore();

    $this->logSubstep('Assert that SUT does not have common files before installation');
    $this->assertCommonFilesAbsent();

    $this->logSubstep('Run the installer to initialise the project with the default settings');
    $this->runInstaller();

    $this->logSubstep('Assert that SUT has common files after installation');
    $this->assertCommonFilesPresent();

    $this->logSubstep('Assert that created SUT is a git repository');
    $this->gitAssertIsRepository(static::$sut);

    $this->logSubstep('Add all Vortex files to new git repository');
    $this->gitCommitAll(static::locationsSut(), 'Added Vortex files');

    $this->logSubstep('Create git-excluded files');
    File::dump(static::locationsSut() . DIRECTORY_SEPARATOR . '.idea/idea_file.txt');

    $this->logStepFinish();
  }

  protected function runInstaller(array $arguments = []): void {
    $this->logNote('Switch to the project root directory');
    chdir(static::locationsRoot());

    if (!is_dir('.vortex/installer/vendor')) {
      $this->logNote('Installing dependencies of the Vortex installer');
      $this->cmd('composer --working-dir=.vortex/installer install');
    }

    $arguments = array_merge([
      '--no-interaction',
      static::locationsSut(),
    ], $arguments);

    $this->cmd(
      'php .vortex/installer/installer.php',
      arg: $arguments,
      env: static::$sutInstallerEnv + [
        // Use a unique temporary directory for each installer run.
        // This is where the installer script downloads the Vortex codebase
        // for processing.
        'VORTEX_INSTALLER_TMP_DIR' => static::locationsTmp(),
        // Point the installer to the local template repository as the source
        // of the Vortex codebase. During development, ensure any pending
        // changes are committed to the template repository.
        'VORTEX_INSTALLER_TEMPLATE_REPO' => static::locationsRoot(),
        // Tests use the demo database and the 'ahoy download-db' command,
        // so we need to point CURL to the test database instead.
        //
        // This overrides the *demo database* with the *test demo database*,
        // which is required for running test assertions ("star wars")
        // against an expected data set.
        //
        // The installer will load this environment variable, and it will
        // take precedence over the value in the .env file.
        'VORTEX_DB_DOWNLOAD_URL' => static::VORTEX_INSTALLER_DEMO_DB_TEST,
      ],
      txt: 'Run the installer'
    );

    $this->logNote('Switch back to the SUT directory after the installer has run');
    chdir(static::locationsSut());

    $this->adjustCodebaseForUnmountedVolumes();

    $this->logNote('Smoke test the installer processing');
    $this->assertDirectoryNotContainsString('.', '#;', message: 'Directory should not contain lines with #;');
    $this->assertDirectoryNotContainsString('.', '#;<', message: 'Directory should not contain lines with #;<');
    $this->assertDirectoryNotContainsString('.', '#;>', message: 'Directory should not contain lines with #;>');
  }

  protected function downloadDatabase(bool $copy_to_container = FALSE): void {
    $this->logStepStart();

    File::remove('.data/db.sql');
    $this->assertFileDoesNotExist('.data/db.sql', 'File .data/db.sql should not exist before downloading the database.');

    $this->cmd(
      './scripts/vortex/download-db.sh',
      env: ['VORTEX_DB_DOWNLOAD_URL' => static::VORTEX_INSTALLER_DEMO_DB_TEST],
      txt: 'Demo database downloaded from ' . static::VORTEX_INSTALLER_DEMO_DB_TEST,
    );

    $this->assertFileExists('.data/db.sql', 'File .data/db.sql should exist after downloading the database.');

    if ($copy_to_container && file_exists('.data/db.sql')) {
      $this->logNote('Copy database file to container');
      $this->cmd('docker compose exec -T cli mkdir -p .data', txt: 'Create .data directory in the container');
      $this->cmd('docker compose cp -L .data/db.sql cli:/app/.data/db.sql', txt: 'Copy database dump into container');
    }

    $this->logStepFinish();
  }

  /**
   * Adjust the codebase for unmounted volumes.
   *
   * This method modifies the codebase files to ensure
   * that the project can be built and run without mounted Docker volumes in
   * environments such as CI/CD pipelines (which also replicate some hosting
   * environments).
   */
  protected function adjustCodebaseForUnmountedVolumes(bool $force = FALSE): void {
    if ($this->volumesMounted() && !$force) {
      $this->logNote('Skipping fixing host dependencies as volumes are mounted');
      return;
    }

    if (File::exists('docker-compose.yml')) {
      $this->logNote('Fixing host dependencies in docker-compose.yml');
      File::removeLine('docker-compose.yml', '###');
      $this->assertFileNotContainsString('docker-compose.yml', '###', 'Lines with ### should be removed from docker-compose.yml');
      File::replaceContentInFile('docker-compose.yml', '##', '');
      $this->assertFileNotContainsString('docker-compose.yml', '##', 'Lines with ## should be removed from docker-compose.yml');
    }

    if (file_exists('.ahoy.yml')) {
      // Override the provision command in .ahoy.yml to copy the database file
      // to
      // the container for when the volumes are not mounted.
      // We are doing this only to replicate developer's workflow and experience
      // when they run `ahoy build` locally.
      $this->logNote('Pre-processing .ahoy.yml to copy database file to container');

      $this->assertFileContainsString(
        '.ahoy.yml',
        'ahoy cli ./scripts/vortex/provision.sh',
        'Initial Ahoy command to provision the container should exist in .ahoy.yml'
      );

      $this->logNote("Patching 'ahoy provision' command to copy the database into container");
      // Replace the command to provision the site in the container with a
      // command that checks for the database file and copies it to the
      // container if it exists.
      // Provision script may be called from multiple sections of the .ahoy.yml
      // file, so we need to ensure that we only modify the one in
      // the 'provision' section.
      File::replaceContentInFile('.ahoy.yml',
        '      ahoy cli ./scripts/vortex/provision.sh',
        '      if [ -f .data/db.sql ]; then docker compose exec cli mkdir -p .data; docker compose cp -L .data/db.sql cli:/app/.data/db.sql; fi; ahoy cli ./scripts/vortex/provision.sh',
      );
    }
  }

  protected function assertCommonFilesPresent(string $webroot = 'web', string $project_name = 'star_wars'): void {
    $this->assertVortexFilesPresent($webroot);

    // Assert that project name is correct.
    $this->assertFileExists('.env');
    $this->assertFileContainsString('.env', 'VORTEX_PROJECT=' . $project_name);

    // Assert that Vortex version was replaced in README.md.
    $this->assertFileExists('README.md');
    $vortex_version = getenv('TEST_VORTEX_VERSION') ?: 'develop';
    $this->assertFileContainsString('README.md', sprintf('badge/Vortex-%s-65ACBC.svg', $vortex_version));
    $this->assertFileContainsString('README.md', 'https://github.com/drevops/vortex/tree/' . $vortex_version);
    $this->assertFileNotContainsString('README.md', 'The following list includes');
    $this->assertFileDoesNotExist('README.dist.md');

    $this->assertFileContainsString('README.md', 'This repository was created using the [Vortex](https://github.com/drevops/vortex) Drupal project template', 'Assert that Vortex footnote remains.');

    // Assert Drupal files are present.
    $this->assertDrupalFilesPresent($webroot);
  }

  protected function assertCommonFilesAbsent(string $webroot = 'web'): void {
    // Example directories and files that should not exist after Vortex removed.
    $this->assertDirectoryDoesNotExist($webroot . '/profiles/custom/your_site_profile');
    $this->assertDirectoryDoesNotExist($webroot . '/modules/custom/ys_base');
    $this->assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Unit/YourSiteExampleUnitTest.php');
    $this->assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Unit/YourSiteCoreUnitTestBase.php');
    $this->assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Kernel/YourSiteExampleKernelTest.php');
    $this->assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Kernel/YourSiteCoreKernelTestBase.php');
    $this->assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Functional/YourSiteExampleFunctionalTest.php');
    $this->assertFileDoesNotExist($webroot . '/modules/custom/ys_base/tests/src/Functional/YourSiteCoreFunctionalTestBase.php');
    $this->assertDirectoryDoesNotExist($webroot . '/modules/custom/ys_search');

    // Example settings files that should not exist.
    $this->assertFileDoesNotExist($webroot . '/sites/default/example.settings.local.php');
    $this->assertFileDoesNotExist($webroot . '/sites/default/example.services.local.yml');

    // Documentation and CI files that should not exist in some contexts.
    $this->assertFileDoesNotExist('docs/faqs.md');
    $this->assertFileDoesNotExist('.ahoy.yml');
    $this->assertFileDoesNotExist('README.md');
    $this->assertFileDoesNotExist('.github/workflows/build-test-deploy.yml');
    $this->assertFileDoesNotExist('.circleci/config.yml');

    // Core Drupal files that should not exist in webroot after Vortex removal.
    $this->assertFileDoesNotExist($webroot . '/sites/default/settings.php');
    $this->assertFileDoesNotExist($webroot . '/sites/default/services.yml');

    // Drupal Scaffold files that should not exist in some contexts.
    $this->assertFileDoesNotExist($webroot . '/.editorconfig');
    $this->assertFileDoesNotExist($webroot . '/.eslintignore');
    $this->assertFileDoesNotExist($webroot . '/.gitattributes');
    $this->assertFileDoesNotExist($webroot . '/autoload.php');
    $this->assertFileDoesNotExist($webroot . '/index.php');
    $this->assertFileDoesNotExist($webroot . '/robots.txt');
    $this->assertFileDoesNotExist($webroot . '/update.php');
  }

  protected function assertVortexFilesPresent(string $webroot = 'web'): void {
    // Docker configuration files.
    $this->assertFileExists('.docker/cli.dockerfile');
    $this->assertFileExists('.docker/database.dockerfile');
    $this->assertFileExists('.docker/nginx-drupal.dockerfile');
    $this->assertFileExists('.docker/php.dockerfile');
    $this->assertFileExists('.docker/solr.dockerfile');
    $this->assertFileExists('.docker/scripts/.gitkeep');
    $this->assertFileExists('.docker/config/database/my.cnf');

    // Solr configuration files.
    $this->assertFileExists('.docker/config/solr/config-set/accents_en.txt');
    $this->assertFileExists('.docker/config/solr/config-set/accents_und.txt');
    $this->assertFileExists('.docker/config/solr/config-set/elevate.xml');
    $this->assertFileExists('.docker/config/solr/config-set/protwords_en.txt');
    $this->assertFileExists('.docker/config/solr/config-set/protwords_und.txt');
    $this->assertFileExists('.docker/config/solr/config-set/schema.xml');
    $this->assertFileExists('.docker/config/solr/config-set/schema_extra_fields.xml');
    $this->assertFileExists('.docker/config/solr/config-set/schema_extra_types.xml');
    $this->assertFileExists('.docker/config/solr/config-set/solrconfig.xml');
    $this->assertFileExists('.docker/config/solr/config-set/solrconfig_extra.xml');
    $this->assertFileExists('.docker/config/solr/config-set/solrconfig_index.xml');
    $this->assertFileExists('.docker/config/solr/config-set/solrconfig_query.xml');
    $this->assertFileExists('.docker/config/solr/config-set/solrconfig_requestdispatcher.xml');
    $this->assertFileExists('.docker/config/solr/config-set/solrcore.properties');
    $this->assertFileExists('.docker/config/solr/config-set/stopwords_en.txt');
    $this->assertFileExists('.docker/config/solr/config-set/stopwords_und.txt');
    $this->assertFileExists('.docker/config/solr/config-set/synonyms_en.txt');
    $this->assertFileExists('.docker/config/solr/config-set/synonyms_und.txt');

    // GitHub files.
    $this->assertFileExists('.github/PULL_REQUEST_TEMPLATE.md');

    // Configuration directories.
    $this->assertDirectoryExists('config/ci');
    $this->assertDirectoryExists('config/default');
    $this->assertDirectoryExists('config/dev');
    $this->assertDirectoryExists('config/local');
    $this->assertDirectoryExists('config/stage');

    // Patches directory.
    $this->assertFileExists('patches/.gitkeep');

    // Script files.
    $this->assertFileExists('scripts/composer/ScriptHandler.php');
    $this->assertFileExists('scripts/custom/.gitkeep');

    // Core Vortex files.
    $this->assertFileExists('scripts/vortex/deploy.sh');
    $this->assertFileExists('scripts/vortex/deploy-artifact.sh');
    $this->assertFileExists('scripts/vortex/deploy-container-registry.sh');
    $this->assertFileExists('scripts/vortex/deploy-lagoon.sh');
    $this->assertFileExists('scripts/vortex/deploy-webhook.sh');
    $this->assertFileExists('scripts/vortex/login-container-registry.sh');
    $this->assertFileExists('scripts/vortex/doctor.sh');
    $this->assertFileExists('scripts/vortex/download-db.sh');
    $this->assertFileExists('scripts/vortex/download-db-acquia.sh');
    $this->assertFileExists('scripts/vortex/download-db-url.sh');
    $this->assertFileExists('scripts/vortex/download-db-ftp.sh');
    $this->assertFileExists('scripts/vortex/download-db-container-registry.sh');
    $this->assertFileExists('scripts/vortex/download-db-lagoon.sh');
    $this->assertFileExists('scripts/vortex/export-db-file.sh');
    $this->assertFileExists('scripts/vortex/export-db-image.sh');
    $this->assertFileExists('scripts/vortex/provision.sh');
    $this->assertFileExists('scripts/vortex/login.sh');
    $this->assertFileExists('scripts/vortex/login-container-registry.sh');
    $this->assertFileExists('scripts/vortex/provision-sanitize-db.sh');
    $this->assertFileExists('scripts/vortex/info.sh');
    $this->assertFileExists('scripts/vortex/notify.sh');
    $this->assertFileExists('scripts/vortex/notify-email.sh');
    $this->assertFileExists('scripts/vortex/notify-github.sh');
    $this->assertFileExists('scripts/vortex/notify-jira.sh');
    $this->assertFileExists('scripts/vortex/notify-newrelic.sh');
    $this->assertFileExists('scripts/vortex/reset.sh');
    $this->assertFileExists('scripts/vortex/task-copy-db-acquia.sh');
    $this->assertFileExists('scripts/vortex/task-copy-files-acquia.sh');
    $this->assertFileExists('scripts/vortex/task-purge-cache-acquia.sh');
    $this->assertFileExists('scripts/vortex/update-vortex.sh');

    $this->assertFileExists('scripts/sanitize.sql');

    // Test files.
    $this->assertFileExists('tests/behat/bootstrap/FeatureContext.php');
    $this->assertDirectoryExists('tests/behat/features');

    // Root configuration files.
    $this->assertFileExists('.ahoy.yml');
    $this->assertFileExists('.dockerignore');
    $this->assertFileExists('.editorconfig');
    $this->assertFileExists('.env');
    $this->assertFileDoesNotExist('.gitattributes');
    $this->assertFileExists('.ahoy.local.example.yml');
    $this->assertFileExists('.env.local.example');
    $this->assertFileExists('.gitignore');
    $this->assertFileExists('behat.yml');
    $this->assertFileExists('composer.json');
    $this->assertFileExists('docker-compose.yml');
    $this->assertFileExists('gherkinlint.json');
    $this->assertFileExists('phpcs.xml');
    $this->assertFileExists('phpstan.neon');
    $this->assertFileExists('phpunit.xml');

    // Documentation files.
    $this->assertFileExists('docs/faqs.md');
    $this->assertFileExists('README.md');
    $this->assertFileExists('docs/releasing.md');
    $this->assertFileExists('docs/testing.md');

    // Assert that Vortex files removed.
    $this->assertDirectoryDoesNotExist('.vortex');
    $this->assertFileDoesNotExist('LICENSE');
    $this->assertFileDoesNotExist('CODE_OF_CONDUCT.md');
    $this->assertFileDoesNotExist('.github/FUNDING.yml');

    $this->assertFileExists('.github/workflows/assign-author.yml');
    $this->assertFileExists('.github/workflows/label-merge-conflict.yml');
    $this->assertFileExists('.github/workflows/draft-release-notes.yml');

    $this->assertFileDoesNotExist('.github/workflows/vortex-release-docs.yml');
    $this->assertFileDoesNotExist('.github/workflows/vortex-test-docs.yml');
    $this->assertFileDoesNotExist('.github/workflows/vortex-test-common.yml');
    $this->assertFileDoesNotExist('.github/workflows/vortex-test-installer.yml');

    if (file_exists('.circleci/config.yml')) {
      $this->assertFileNotContainsString('.circleci/config.yml', 'vortex-dev', 'CircleCI config should not contain development Vortex references');
    }

    // Assert that documentation was processed correctly.
    if (file_exists('README.md')) {
      $this->assertFileNotContainsString('README.md', '# Vortex');
    }

    // Check directory doesn't contain .vortex references.
    $this->assertDirectoryNotContainsString('.', '/\.vortex');
  }

  protected function assertDrupalFilesPresent(string $webroot = 'web'): void {
    // Stub profile removed.
    $this->assertDirectoryDoesNotExist($webroot . '/profiles/custom/your_site_profile');
    // Stub code module removed.
    $this->assertDirectoryDoesNotExist($webroot . '/modules/custom/ys_base');
    // Stub theme removed.
    $this->assertDirectoryDoesNotExist($webroot . '/themes/custom/your_site_theme');

    // Site core module created.
    $this->assertDirectoryExists($webroot . '/modules/custom/sw_base');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/sw_base.info.yml');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/sw_base.module');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/sw_base.deploy.php');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Unit/SwBaseUnitTestBase.php');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Unit/ExampleTest.php');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Kernel/SwBaseKernelTestBase.php');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Kernel/ExampleTest.php');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Functional/SwBaseFunctionalTestBase.php');
    $this->assertFileExists($webroot . '/modules/custom/sw_base/tests/src/Functional/ExampleTest.php');

    // Site search module created.
    $this->assertDirectoryExists($webroot . '/modules/custom/sw_search');
    $this->assertFileExists($webroot . '/modules/custom/sw_search/sw_search.info.yml');

    // Drupal Scaffold files exist.
    $this->assertFileDoesNotExist($webroot . '/.editorconfig');
    $this->assertFileDoesNotExist($webroot . '/.eslintignore');
    $this->assertFileDoesNotExist($webroot . '/.eslintrc.json');
    $this->assertFileDoesNotExist($webroot . '/.gitattributes');
    $this->assertFileExists($webroot . '/autoload.php');
    $this->assertFileExists($webroot . '/index.php');
    $this->assertFileDoesNotExist($webroot . '/robots.txt');
    $this->assertFileDoesNotExist($webroot . '/update.php');

    // Settings files exist.
    $this->assertFileExists($webroot . '/sites/default/settings.php');
    $this->assertDirectoryExists($webroot . '/sites/default/includes/');
    $this->assertFileExists($webroot . '/sites/default/default.settings.php');
    $this->assertFileExists($webroot . '/sites/default/default.services.yml');
    $this->assertFileExists($webroot . '/sites/default/example.settings.local.php');
    $this->assertFileExists($webroot . '/sites/default/example.services.local.yml');

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

  protected function assertThemeFilesPresent(string $webroot = 'web'): void {
    $this->assertDirectoryExists($webroot . '/themes/custom/star_wars');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/js/star_wars.js');
    $this->assertDirectoryExists($webroot . '/themes/custom/star_wars/scss');
    $this->assertDirectoryExists($webroot . '/themes/custom/star_wars/images');
    $this->assertDirectoryExists($webroot . '/themes/custom/star_wars/fonts');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/.gitignore');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/star_wars.info.yml');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/star_wars.libraries.yml');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/star_wars.theme');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/Gruntfile.js');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/package.json');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/yarn.lock');

    $this->assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Unit/StarWarsUnitTestBase.php');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Unit/ExampleTest.php');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Kernel/StarWarsKernelTestBase.php');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Kernel/ExampleTest.php');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Functional/StarWarsFunctionalTestBase.php');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/tests/src/Functional/ExampleTest.php');
  }

  protected function assertThemeFilesAbsent(string $webroot = 'web'): void {
    $this->assertDirectoryDoesNotExist($webroot . '/themes/custom/your_site_theme');
  }

  protected function assertFilesTrackedInGit(string $webroot = 'web', bool $skip_commit = FALSE): void {
    $this->createDevelopmentSettings($webroot);

    if (!$skip_commit) {
      $this->gitCommitAll('.', 'Commit fully built project');
    }

    // Assert that Drupal Scaffold files were added to the git repository.
    $this->gitAssertFilesTracked($webroot . '/autoload.php');
    $this->gitAssertFilesTracked($webroot . '/index.php');
    $this->gitAssertFilesNotTracked($webroot . '/robots.txt');
    $this->gitAssertFilesNotTracked($webroot . '/update.php');

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

    $this->removeDevelopmentSettings($webroot);
  }

  protected function prepareGlobalGitconfig(): void {
    shell_exec('git config --global init.defaultBranch >/dev/null || git config --global init.defaultBranch "main"');
  }

  protected function prepareGlobalGitignore(): void {
    $current_excludes_file = trim(shell_exec('git config --global core.excludesfile 2>/dev/null') ?: '');

    if (empty($_SERVER['HOME']) || !is_string($_SERVER['HOME']) || !is_dir($_SERVER['HOME'])) {
      throw new \RuntimeException('Unable to determine user home directory from $_SERVER["HOME"].');
    }

    $filename = empty($current_excludes_file) ? strval($_SERVER['HOME']) . '/.gitignore' : $current_excludes_file;

    if (File::exists($filename)) {
      $this->logNote('Global excludes file already exists: ' . $filename);
      return;
    }

    $content = <<<EOT
##
## Temporary files generated by various OSs and IDEs
##
Thumbs.db
._*
.DS_Store
.idea
.idea/*
*.sublime*
.project
.netbeans
.vscode
.vscode/*
nbproject
nbproject/*
EOT;

    File::dump($filename, $content);
    $this->logNote('Created global excludes file: ' . $filename);

    if (empty($current_excludes_file)) {
      shell_exec('git config --global core.excludesfile ' . escapeshellarg($filename));
      $this->logNote('Configured git to use global excludes file: ' . $filename);
    }
  }

  protected function createDevelopmentSettings(string $webroot = 'web'): void {
    $this->logNote('Create local settings');

    $this->assertFileExists($webroot . '/sites/default/example.settings.local.php');
    $this->assertFileExists($webroot . '/sites/default/example.services.local.yml');

    File::copy($webroot . '/sites/default/example.settings.local.php', $webroot . '/sites/default/settings.local.php');
    $this->assertFileExists($webroot . '/sites/default/settings.local.php', 'Manually created local settings file exists.');

    File::copy($webroot . '/sites/default/example.services.local.yml', $webroot . '/sites/default/services.local.yml');
    $this->assertFileExists($webroot . '/sites/default/services.local.yml', 'Manually created local services file exists.');
  }

  protected function removeDevelopmentSettings(string $webroot = 'web'): void {
    $this->logNote('Remove local settings');

    File::remove([
      $webroot . '/sites/default/settings.local.php',
      $webroot . '/sites/default/services.local.yml',
    ]);
    $this->assertFileDoesNotExist($webroot . '/sites/default/settings.local.php', 'Manually created local settings file has been removed.');
    $this->assertFileDoesNotExist($webroot . '/sites/default/services.local.yml', 'Manually created local services file has been removed.');
  }

  public function volumesMounted(): bool {
    return getenv('VORTEX_DEV_VOLUMES_SKIP_MOUNT') != 1;
  }

  public function forceVolumesUnmounted(): void {
    putenv('VORTEX_DEV_VOLUMES_SKIP_MOUNT=1');
  }

  public function syncToHost(string|array $files = []): void {
    if ($this->volumesMounted()) {
      return;
    }

    $files = array_filter(is_array($files) ? $files : [$files]);
    if (empty($files)) {
      $this->logNote('Syncing all files from container to host');
      shell_exec('docker compose cp -L cli:/app/. . > /dev/null 2>&1');
    }
    else {
      foreach ($files as $file) {
        $this->logNote('Syncing file from container to host: ' . $file);
        shell_exec('docker compose cp -L cli:/app/' . escapeshellarg($file) . ' ' . escapeshellarg($file));
      }
    }
  }

  public function syncToContainer(string|array $files = []): void {
    if ($this->volumesMounted()) {
      return;
    }

    $files = array_filter(is_array($files) ? $files : [$files]);
    if (empty($files)) {
      $this->logNote('Syncing all files from host to container');
      shell_exec('docker compose cp -L . cli:/app/ > /dev/null 2>&1');
    }
    else {
      foreach ($files as $file) {
        if (!file_exists($file)) {
          throw new \InvalidArgumentException('Unable to sync file - file does not exist: ' . $file);
        }

        $this->logNote('Syncing file from host to container: ' . $file);
        shell_exec('docker compose cp -L ' . escapeshellarg($file) . ' cli:/app/' . escapeshellarg($file));
      }
    }
  }

  protected function backupFile(string $file): void {
    $backup_dir = static::$tmp . '/bkp';
    if (!is_dir($backup_dir)) {
      mkdir($backup_dir, 0755, TRUE);
    }
    File::copy($file, $backup_dir . '/' . basename($file));
  }

  protected function restoreFile(string $file): void {
    $backup_file = static::$tmp . '/bkp/' . basename($file);
    if (file_exists($backup_file)) {
      File::copy($backup_file, $file);
    }
  }

  protected function addVarToFile(string $file, string $var, string $value): void {
    // Backup original file first.
    $this->backupFile($file);
    $content = File::read($file);
    $content .= sprintf('%s%s=%s%s', PHP_EOL, $var, $value, PHP_EOL);
    File::dump($file, $content);
  }

  protected function trimFile(string $file): void {
    $content = File::read($file);
    $lines = explode("\n", $content);
    // Remove last line.
    array_pop($lines);
    File::dump($file, implode("\n", $lines));
  }

  /**
   * {@inheritdoc}
   */
  public function ignoredPaths(): array {
    return [
      '.7z',
      '.avif',
      '.bz2',
      '.gz',
      '.heic',
      '.heif',
      '.pdf',
      '.rar',
      '.tar',
      '.woff',
      '.woff2',
      '.xz',
      '.zip',
      '.bmp',
      '.gif',
      '.ico',
      '.jpeg',
      '.jpg',
      '.png',
      '.svg',
      '.svgz',
      '.tif',
      '.tiff',
      '.webp',
      'modules.README.txt',
      'modules/README.txt',
      'themes.README.txt',
      'themes/README.txt',
    ];
  }

}
