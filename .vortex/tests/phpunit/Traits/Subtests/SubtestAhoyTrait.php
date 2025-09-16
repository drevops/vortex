<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;

/**
 * Provides Ahoy command subtests.
 */
trait SubtestAhoyTrait {

  protected function subtestAhoyCli(): void {
    $this->logStepStart();

    $this->cmd(
      'ahoy cli "echo Test from inside of the container"',
      [
        '! Containers are not running.',
        '* Test from inside of the container',
      ],
      '`ahoy cli` can execute commands inside the container'
    );

    // Set test environment variables.
    putenv('DRUPAL_UNFILTERED_VAR=drupalvar');
    putenv('OTHER_FILTERED_VAR=othervar');

    $this->cmd(
      'ahoy cli "echo $DRUPAL_UNFILTERED_VAR"',
      ['* drupalvar', '! othervar'],
      env: [
        'DRUPAL_UNFILTERED_VAR' => 'drupalvar',
        'OTHER_FILTERED_VAR' => 'othervar',
      ],
      txt: '`ahoy cli` passes only allowed environment variables into the container'
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyComposer(): void {
    $this->logStepStart();

    $this->cmd(
      'ahoy composer about',
      [
        'Composer - Dependency Manager for PHP - version 2.',
        'Composer is a dependency manager tracking local dependencies of your projects and libraries.',
      ],
      '`ahoy composer` can execute composer commands inside the container'
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyDrush(): void {
    $this->logStepStart();

    $this->cmd('ahoy drush st', '! Containers are not running.', '`ahoy drush` can execute drush commands inside the container');

    $this->logStepFinish();
  }

  protected function subtestAhoyInfo(string $webroot = 'web', string $db_image = ''): void {
    $this->logStepStart();

    $this->cmd(
      'ahoy info',
      [
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
        ($db_image !== '' && $db_image !== '0' ? '*' : '!') . ' DB-in-image                 : ' . $db_image,
        '! Containers are not running.',
      ],
      '`ahoy info` shows correct project information'
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyContainerLogs(): void {
    $this->logStepStart();

    $this->cmd('ahoy logs', ['* cli', '! Containers are not running.'], '`ahoy logs` can be executed');

    $this->logStepFinish();
  }

  protected function subtestAhoyLogin(): void {
    $this->logStepStart();

    $this->cmd('ahoy login', ['* http', '! Containers are not running.'], '`ahoy login` can be executed');

    $this->logStepFinish();
  }

  protected function subtestAhoyExportDb(string $filename = ''): void {
    $this->logStepStart();

    $has_argument = $filename !== '';

    $this->logSubstep('Testing ahoy export-db command');
    $this->cmd(
      'ahoy export-db',
      arg: $has_argument ? [$filename] : [],
      out: [
        '* Exported database dump saved',
        '! Containers are not running.',
      ],
      txt: 'Export database dump ' . ($has_argument ? sprintf("to file '%s'", $filename) : 'to a default file')
    );

    $this->syncToHost();

    if ($has_argument) {
      $this->assertFileExists('.data/' . $filename, 'Export file should exist after export');
    }
    else {
      $this->assertFilesWildcardExists('.data/export_db_*');
    }

    $this->logStepFinish();
  }

  protected function subtestAhoyImportDb(string $filename = ''): void {
    $this->logStepStart();

    $has_argument = $filename !== '';

    $this->cmd(
      'ahoy import-db',
      arg: $has_argument ? [$filename] : [],
      out: [
        '* Provisioning site from the database dump file.',
        "! Running deployment operations via 'drush deploy:hook'.",
        '! Running database updates.',
      ],
      txt: 'Import database dump ' . ($has_argument ? sprintf("from file '%s'", $filename) : 'from the default file')
    );

    $this->logStepFinish();
  }

  protected function subtestAhoyProvision(): void {
    $this->logStepStart();

    $this->logSubstep('Run initial provision');

    $this->cmd(
      'ahoy provision',
      [
        'Provisioning site from the database dump file.',
        "Running deployment operations via 'drush deploy:hook'.",
        'Running database updates.',
      ],
      'Initial provision should complete successfully'
    );

    $this->logSubstep('Run follow-up provision with exported config files matching DB');

    $this->cmd('ahoy drush cex -y', '* ../config/default', 'Export configuration should complete successfully');
    $this->cmd('ahoy export-db db.sql', '* Exported database dump saved', 'Export database should complete successfully');
    $this->syncToHost();

    $this->cmd(
      'ahoy provision',
      [
        '* Provisioning site from the database dump file.',
        // @note 'drush deploy:hook' runs only if config files exist.
        "* Running deployment operations via 'drush deploy'.",
        '! Import the listed configuration changes',
        '* There are no changes to import',
      ],
      'Follow-up provision with matching config should complete successfully'
    );

    $this->cmd('ahoy drush config:status', '! Different', 'Config files should match the DB');

    $this->logSubstep('Run provision with exported config files different to DB');

    $this->logNote('Make a change to the configuration.');
    File::replaceContentInFile('config/default/system.site.yml', 'admin_compact_mode: false', 'admin_compact_mode: true');
    $this->syncToContainer();

    $this->cmd('ahoy drush config:status', 'Different', 'Config files should differ from the DB');

    $this->cmd(
      'ahoy provision',
      [
        'Provisioning site from the database dump file.',
        "Running deployment operations via 'drush deploy'.",
        'Import the listed configuration changes',
      ],
      'Provision with updated config should complete successfully'
    );

    $this->logSubstep('Test that provision works without DB');
    $this->cmd('ahoy drush sql:drop -y', txt: 'Database should be dropped successfully');

    $this->cmd(
      'ahoy provision',
      [
        'Provisioning site from the database dump file.',
        "Running deployment operations via 'drush deploy'.",
      ],
      'Provision without DB should complete successfully'
    );

    $this->logStepFinish();
  }

}
