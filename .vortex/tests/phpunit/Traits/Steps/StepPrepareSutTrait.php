<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;

/**
 * Provides system under test preparation step.
 */
trait StepPrepareSutTrait {

  protected function stepPrepareSut(): void {
    $this->logStepStart();

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
      env: [
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
    $this->assertDirectoryNotContainsString('.', '#;');
    $this->assertDirectoryNotContainsString('.', '#;<');
    $this->assertDirectoryNotContainsString('.', '#;>');
  }

  /**
   * Adjust the codebase for unmounted volumes.
   *
   * This method modifies the codebase files to ensure
   * that the project can be built and run without mounted Docker volumes in
   * environments such as CI/CD pipelines (which also replicate some hosting
   * environments).
   */
  protected function adjustCodebaseForUnmountedVolumes(): void {
    if ($this->volumesMounted()) {
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

}
