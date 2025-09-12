<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides Ahoy command testing steps.
 */
trait StepAhoyTrait {

  use LoggerTrait;

  protected function stepAhoyCli(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy cli command');
    $this->processRun('ahoy cli "echo Test from inside of the container"');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputNotContains('Containers are not running.');
    $this->assertProcessOutputContains('Test from inside of the container');

    $this->logSubstep('Testing environment variable filtering');
    // Set test environment variables.
    putenv('DRUPAL_UNFILTERED_VAR=drupalvar');
    putenv('OTHER_FILTERED_VAR=othervar');

    $this->processRun('ahoy cli "echo $DRUPAL_UNFILTERED_VAR"', env: [
      'DRUPAL_UNFILTERED_VAR' => 'drupalvar',
      'OTHER_FILTERED_VAR' => 'othervar',
    ]);
    $this->assertProcessOutputContains('drupalvar');
    $this->assertProcessOutputNotContains('othervar');

    $this->logStepFinish();
  }

  protected function stepAhoyComposer(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy composer command');
    $this->processRun('ahoy composer about');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Composer - Dependency Manager for PHP - version 2.');
    $this->assertProcessOutputContains('Composer is a dependency manager tracking local dependencies of your projects and libraries.');

    $this->logStepFinish();
  }

  protected function stepAhoyDrush(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy drush command');
    $this->processRun('ahoy drush st');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputNotContains('Containers are not running.');

    $this->logStepFinish();
  }

  protected function stepAhoyInfo(string $webroot = 'web', string $db_image = ''): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy info command');
    $this->processRun('ahoy info');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Project name                : star_wars');
    $this->assertProcessOutputContains('Docker Compose project name : star_wars');
    $this->assertProcessOutputContains('Site local URL              : http://star_wars.docker.amazee.io');
    $this->assertProcessOutputContains('Path to web root            : /app/' . $webroot);
    $this->assertProcessOutputContains('DB host                     : database');
    $this->assertProcessOutputContains('DB username                 : drupal');
    $this->assertProcessOutputContains('DB password                 : drupal');
    $this->assertProcessOutputContains('DB port                     : 3306');
    $this->assertProcessOutputContains('DB port on host             :');

    if (!empty($db_image)) {
      $this->assertProcessOutputContains('DB-in-image                 : ' . $db_image);
    }
    else {
      $this->assertProcessOutputNotContains('DB-in-image             : ' . $db_image);
    }

    $this->assertProcessOutputContains('Solr URL on host            :');
    $this->assertProcessOutputContains('Selenium VNC URL on host    :');
    $this->assertProcessOutputContains('Mailhog URL                 : http://mailhog.docker.amazee.io/');
    $this->assertProcessOutputContains("Xdebug                      : Disabled ('ahoy debug' to enable)");
    $this->assertProcessOutputNotContains('Containers are not running.');

    $this->logStepFinish();
  }

  protected function stepAhoyContainerLogs(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy logs command');
    $this->processRun('ahoy logs');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputNotContains('Containers are not running.');

    $this->logStepFinish();
  }

  protected function stepAhoyLogin(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy login command');
    $this->processRun('ahoy login');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputNotContains('Containers are not running.');

    $this->logStepFinish();
  }

}
