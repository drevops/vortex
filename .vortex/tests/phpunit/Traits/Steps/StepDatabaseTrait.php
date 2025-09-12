<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\LoggerTrait;

/**
 * Provides database operation testing steps.
 */
trait StepDatabaseTrait {

  use LoggerTrait;

  protected function stepAhoyExportDb(string $filename = ''): void {
    $this->logStepStart();

    $this->logSubstep('Testing ahoy export-db command');
    $this->processRun('ahoy export-db', $filename !== '' ? [$filename] : []);
    $this->assertProcessSuccessful();
    $this->assertProcessOutputNotContains('Containers are not running.');

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
    $this->processRun('ahoy import-db', $filename !== '' && $filename !== '0' ? [$filename] : []);
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Provisioning site from the database dump file.');
    $this->assertProcessOutputNotContains("Running deployment operations via 'drush deploy:hook'.");
    $this->assertProcessOutputNotContains('Running database updates.');

    $this->logStepFinish();
  }

  protected function stepAhoyProvision(): void {
    $this->logStepStart();

    // Phase 1.
    $this->logSubstep('Run initial provision');
    $this->processRun('ahoy provision');
    $this->assertProcessSuccessful();

    $this->assertProcessOutputContains('Provisioning site from the database dump file.');
    // Assert that config files do not exist.
    $this->assertProcessOutputContains("Running deployment operations via 'drush deploy:hook'.");
    $this->assertProcessOutputContains('Running database updates.');

    $this->logSubstep('Export config');
    $this->processRun('ahoy drush cex -y');
    $this->assertProcessSuccessful();
    $this->syncToHost();

    // Phase 2.
    $this->logSubstep('Dump database');
    $this->processRun('ahoy export-db db.sql');
    $this->assertProcessSuccessful();
    $this->syncToHost();

    $this->logSubstep('Run follow-up provision with exported config files matching DB');
    $this->processRun('ahoy provision');
    $this->assertProcessSuccessful();

    $this->assertProcessOutputContains('Provisioning site from the database dump file.');
    // @note 'drush deploy:hook' runs only if config files exist.
    $this->assertProcessOutputContains("Running deployment operations via 'drush deploy'.");
    $this->assertProcessErrorOutputContains('There are no changes to import');
    $this->assertProcessOutputNotContains('Import the listed configuration changes');

    $this->logSubstep('Check that config files are not different to DB');
    $this->processRun('ahoy drush config:status');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputNotContains('Different');

    $this->logSubstep('Make a change to the configuration.');
    File::replaceContentInFile('config/default/system.site.yml', 'admin_compact_mode: false', 'admin_compact_mode: true');
    $this->syncToContainer();

    $this->logSubstep('Check that config files are different to DB');
    $this->processRun('ahoy drush config:status');
    $this->assertProcessSuccessful();
    $this->assertProcessOutputContains('Different');

    // Phase 3.
    $this->logSubstep('Run provision with exported config files different to DB');
    $this->processRun('ahoy provision');
    $this->assertProcessSuccessful();

    $this->assertProcessOutputContains('Provisioning site from the database dump file.');
    // @note 'drush deploy:hook' runs only if config files exist.
    $this->assertProcessOutputContains("Running deployment operations via 'drush deploy'.");
    $this->assertProcessOutputContains('Import the listed configuration changes');

    // Phase 4.
    $this->logSubstep('Drop database to test that provision works without DB');
    $this->processRun('ahoy drush sql:drop -y');
    $this->assertProcessSuccessful();

    $this->logSubstep('Run provision without DB');
    $this->processRun('ahoy provision');
    $this->assertProcessSuccessful();

    $this->assertProcessOutputContains('Provisioning site from the database dump file.');
    // @note 'drush deploy:hook' runs only if config files exist.
    $this->assertProcessOutputContains("Running deployment operations via 'drush deploy'.");

    $this->logStepFinish();
  }

}
