<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides system under test preparation step.
 */
trait StepPrepareSutTrait {

  use LoggerTrait;
  use StepDownloadDbTrait;

  protected function stepPrepareSut(): void {
    $this->logStepStart();

    $this->logSubstep('Check that SUT does not have common files before installation');
    $this->assertCommonFilesAbsent();

    $this->logSubstep('Run installer to initialise the project with default settings');
    $this->runInstaller();

    $this->logSubstep('Check that SUT has common files after installation');
    $this->assertCommonFilesPresent();

    $this->logSubstep('Check that created SUT is a git repository');
    $this->gitAssertIsRepository(static::$sut);

    $this->logSubstep('Add all Vortex files to new git repo');
    $this->gitCommitAll(static::locationsSut(), 'Add all Vortex files to new git repo');

    $this->logSubstep('Create IDE config file');
    File::dump(static::locationsSut() . DIRECTORY_SEPARATOR . '.idea/idea_file.txt');

    $this->logStepFinish();
  }

  protected function runInstaller(array $arguments = []): void {
    chdir(static::locationsRoot());

    if (!is_dir('.vortex/installer/vendor')) {
      $this->log('Installing dependencies of the Vortex installer');
      $this->processRun('composer --working-dir=.vortex/installer install');
    }

    $this->processRun('php .vortex/installer/installer.php --no-interaction ' . static::locationsSut(), $arguments, env: [
      // Force the installer script to be downloaded from the local repo for
      // testing.
      'VORTEX_INSTALLER_TEMPLATE_REPO' => static::locationsRoot(),
      // Tests are using demo database and 'ahoy download-db' command, so we
      // need
      // to set the CURL DB to test DB.
      //
      // Override demo database with test demo database. This is required to
      // use
      // test assertions ("star wars") with demo database.
      //
      // Installer will load environment variable and it will take precedence
      // over
      // the value in .env file.
      'VORTEX_DB_DOWNLOAD_URL' => static::VORTEX_INSTALLER_DEMO_DB_TEST,
      // Use unique installer temporary directory for each run. This is where
      // the installer script downloads the Vortex codebase for processing.
      'VORTEX_INSTALLER_TMP_DIR' => static::locationsTmp(),
    ]);
    $this->assertProcessSuccessful();

    // Switch to the SUT directory after the installer has run.
    chdir(static::locationsSut());

    // Adjust the codebase for unmounted volumes.
    $this->adjustCodebaseForUnmountedVolumes();
  }

  /**
   * Adjust the codebase for unmounted volumes.
   *
   * This method modifies the codebase files to ensure
   * that the project can be built and run without mounted Docker volumes.
   */
  protected function adjustCodebaseForUnmountedVolumes(): void {
    if ($this->volumesMounted()) {
      $this->logSubstep('Skipping fixing host dependencies as volumes are mounted');
      return;
    }

    if (File::exists('docker-compose.yml')) {
      $this->logSubstep('Fixing host dependencies in docker-compose.yml');
      File::removeLine('docker-compose.yml', '###');
      File::replaceContent('docker-compose.yml', '##', '');
    }

    if (file_exists('.ahoy.yml')) {
      // Override the provision command in .ahoy.yml to copy the database file
      // to
      // the container for when the volumes are not mounted.
      // We are doing this only to replicate developer's workflow and experience
      // when they run `ahoy build` locally.
      $this->logSubstep('Pre-processing .ahoy.yml to copy database file to container');

      $this->assertFileContainsString(
        'ahoy cli ./scripts/vortex/provision.sh',
        '.ahoy.yml',
        'Initial Ahoy command to provision the container should exist in .ahoy.yml'
      );

      // Replace the command to provision the container with a command that
      // checks for the database file and copies it to the container if it
      // exists.
      // Provision script may be called from multiple sections of the .ahoy.yml
      // file, so we need to ensure that we only modify the one in
      // the 'provision' section.
      File::replaceContent('.ahoy.yml',
        '      ahoy cli ./scripts/vortex/provision.sh',
        '      if [ -f .data/db.sql ]; then docker compose exec cli mkdir -p .data; docker compose cp -L .data/db.sql cli:/app/.data/db.sql; fi; ahoy cli ./scripts/vortex/provision.sh',
      );
    }
  }

}
