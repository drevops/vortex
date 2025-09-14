<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;

/**
 * Provides database operation testing steps.
 */
trait StepDatabaseTrait {

  protected function stepAhoyExportDb(string $filename = ''): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy export-db command');
    $this->cmd('ahoy export-db', '! Containers are not running.', arg: $filename !== '' ? [$filename] : []);

    $this->syncToHost();

    $this->logSubstep('Verify export file was created');
    if ($filename !== '' && $filename !== '0') {
      $this->assertFileExists('.data/' . $filename, 'Export file should exist after export');
    }
    else {
      $this->assertFilesWildcardExists('.data/export_db_*');
    }

    $this->logStepFinish();
  }

  protected function stepAhoyImportDb(string $filename = ''): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy import-db command');
    $this->cmd('ahoy import-db', [
      '* Provisioning site from the database dump file.',
      "! Running deployment operations via 'drush deploy:hook'.",
      '! Running database updates.',
    ], arg: $filename !== '' && $filename !== '0' ? [$filename] : []);

    $this->logStepFinish();
  }

  protected function stepAhoyProvision(): void {
    $this->logStepStart();

    // Phase 1.
    $this->logSubstep('Run initial provision');
    $this->cmd('ahoy provision', [
      'Provisioning site from the database dump file.',
      "Running deployment operations via 'drush deploy:hook'.",
      'Running database updates.',
    ]);

    $this->logSubstep('Export config');
    $this->cmd('ahoy drush cex -y');
    $this->syncToHost();

    // Phase 2.
    $this->logSubstep('Dump database');
    $this->cmd('ahoy export-db db.sql');
    $this->syncToHost();

    $this->logSubstep('Run follow-up provision with exported config files matching DB');
    $this->cmd('ahoy provision', [
      '* Provisioning site from the database dump file.',
      // @note 'drush deploy:hook' runs only if config files exist.
      "* Running deployment operations via 'drush deploy'.",
      '! Import the listed configuration changes',
      '* There are no changes to import',
    ]);

    $this->logSubstep('Check that config files are not different to DB');
    $this->cmd('ahoy drush config:status', '! Different');

    $this->logSubstep('Make a change to the configuration.');
    File::replaceContentInFile('config/default/system.site.yml', 'admin_compact_mode: false', 'admin_compact_mode: true');
    $this->syncToContainer();

    $this->logSubstep('Check that config files are different to DB');
    $this->cmd('ahoy drush config:status', 'Different');

    // Phase 3.
    $this->logSubstep('Run provision with exported config files different to DB');
    $this->cmd('ahoy provision', [
      'Provisioning site from the database dump file.',
      "Running deployment operations via 'drush deploy'.",
      'Import the listed configuration changes',
    ]);

    // Phase 4.
    $this->logSubstep('Drop database to test that provision works without DB');
    $this->cmd('ahoy drush sql:drop -y');

    $this->logSubstep('Run provision without DB');
    $this->cmd('ahoy provision', [
      'Provisioning site from the database dump file.',
      "Running deployment operations via 'drush deploy'.",
    ]);

    $this->logStepFinish();
  }

}
