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
    $this->cmd('ahoy cli "echo Test from inside of the container"', [
      '! Containers are not running.',
      '* Test from inside of the container',
    ]);

    $this->logSubstep('Testing environment variable filtering');
    // Set test environment variables.
    putenv('DRUPAL_UNFILTERED_VAR=drupalvar');
    putenv('OTHER_FILTERED_VAR=othervar');

    $this->cmd('ahoy cli "echo $DRUPAL_UNFILTERED_VAR"', ['* drupalvar', '! othervar'], env: [
      'DRUPAL_UNFILTERED_VAR' => 'drupalvar',
      'OTHER_FILTERED_VAR' => 'othervar',
    ]);

    $this->logStepFinish();
  }

  protected function stepAhoyComposer(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy composer command');
    $this->cmd('ahoy composer about', [
      'Composer - Dependency Manager for PHP - version 2.',
      'Composer is a dependency manager tracking local dependencies of your projects and libraries.',
    ]);

    $this->logStepFinish();
  }

  protected function stepAhoyDrush(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy drush command');
    $this->cmd('ahoy drush st', '! Containers are not running.');

    $this->logStepFinish();
  }

  protected function stepAhoyInfo(string $webroot = 'web', string $db_image = ''): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy info command');
    $this->cmd('ahoy info', [
      '* Project name                : star_wars',
      '* Docker Compose project name : star_wars',
      '* Site local URL              : http://star_wars.docker.amazee.io',
      '* Path to web root            : /app/' . $webroot,
      '* DB host                     : database',
      '* DB username                 : drupal',
      '* DB password                 : drupal',
      '* DB port                     : 3306',
      '* DB port on host             :',
      '* Solr URL on host            :',
      '* Selenium VNC URL on host    :',
      '* Mailhog URL                 : http://mailhog.docker.amazee.io/',
      "* Xdebug                      : Disabled ('ahoy debug' to enable)",
      '! Containers are not running.',
    ]);

    if (!empty($db_image)) {
      $this->assertProcessOutputContains('DB-in-image                 : ' . $db_image);
    }
    else {
      $this->assertProcessOutputNotContains('DB-in-image             : ' . $db_image);
    }

    $this->logStepFinish();
  }

  protected function stepAhoyContainerLogs(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy logs command');
    $this->cmd('ahoy logs', '! Containers are not running.');

    $this->logStepFinish();
  }

  protected function stepAhoyLogin(): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy login command');
    $this->cmd('ahoy login', '! Containers are not running.');

    $this->logStepFinish();
  }

}
