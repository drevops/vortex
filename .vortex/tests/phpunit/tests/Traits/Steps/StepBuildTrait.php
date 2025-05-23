<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides build step.
 */
trait StepBuildTrait {

  use LoggerTrait;

  protected function stepBuild(string $webroot = 'web'): void {
    $this->logStepStart();

    $db_file_present = file_exists('.data/db.sql');
    $this->logSubstep('Database file exists before build: ' . ($db_file_present ? 'Yes' : 'No'));

    $this->logSubstep('Starting ahoy build');
    $this->processOutputShow();

    $this->processRun('ahoy', ['build'], ['y'], [
      // Tests are using demo database and 'ahoy download-db' command, so we
      // need
      // to set the CURL DB to test DB.
      //
      // Override demo database with test demo database. This is required to use
      // test assertions ("star wars") with demo database.
      //
      // Ahoy will load environment variable, and it will take precedence over
      // the value in .env file.
      'VORTEX_DB_DOWNLOAD_URL' => static::VORTEX_INSTALL_DEMO_DB_TEST,
      // Credentials for the test container registry to allow fetching public
      // images to overcome the throttle limit of Docker Hub, and also used for
      // pushing images during the build.
      'VORTEX_CONTAINER_REGISTRY_USER' => getenv('TEST_VORTEX_CONTAINER_REGISTRY_USER') ?: '',
      'VORTEX_CONTAINER_REGISTRY_PASS' => getenv('TEST_VORTEX_CONTAINER_REGISTRY_PASS') ?: '',
    ], 10 * 60);
    $this->logSubstep('Finished ahoy build');
    $this->assertProcessSuccessful();
    $this->processOutputHide();
    $this->syncToHost();

    $this->logSubstep('Assert that lock files were created');
    $this->assertFileExists('composer.lock', 'Composer lock file should exist after build');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/yarn.lock', 'Yarn lock file should exist after build');

    // Assert that database file preserved after build if existed before.
    if ($db_file_present) {
      $this->logSubstep('Assert that database file was preserved after build');
      $this->assertFileExists('.data/db.sql', 'Database file should be preserved after build if it existed before');
    }
    else {
      $this->logSubstep('Assert that database file was not created after build');
      $this->assertFileDoesNotExist('.data/db.sql', 'Database file should not exist if it did not exist before build');
    }

    $this->logSubstep('Assert common files are present after build');
    $this->assertCommonFilesPresent($webroot);

    $this->logSubstep('Assert only minified compiled CSS exists');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/build/css/star_wars.min.css', 'Minified CSS file should exist');
    $this->assertFileNotContainsString('background: #7e57e2', $webroot . '/themes/custom/star_wars/build/css/star_wars.min.css', 'CSS should not contain development colors');
    $this->assertFileDoesNotExist($webroot . '/themes/custom/star_wars/build/css/star_wars.css', 'Non-minified CSS should not exist');

    $this->logSubstep('Assert only minified compiled JS exists');
    $this->assertFileExists($webroot . '/themes/custom/star_wars/build/js/star_wars.min.js', 'Minified JS file should exist');
    $this->assertFileContainsString('!function(Drupal){"use strict";Drupal.behaviors.star_wars', $webroot . '/themes/custom/star_wars/build/js/star_wars.min.js', 'JS should contain expected minified content');
    $this->assertFileDoesNotExist($webroot . '/themes/custom/star_wars/build/js/star_wars.js', 'Non-minified JS should not exist');

    $this->logStepFinish();
  }

}
