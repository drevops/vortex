<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits\Steps;

use AlexSkrypnyk\File\File;

/**
 * Provides database download step.
 */
trait StepDownloadDbTrait {

  /**
   * URL to the test demo database.
   *
   * Tests use demo database and 'ahoy download-db' command, so we need
   * to set the CURL DB to test DB.
   */
  const VORTEX_INSTALL_DEMO_DB_TEST = 'https://github.com/drevops/vortex/releases/download/25.4.0/db_d11_2.test.sql';

  protected function stepDownloadDb(): void {
    $this->logStepStart();

    File::remove('.data/db.sql');
    $this->assertFileDoesNotExist('.data/db.sql', 'File .data/db.sql should not exist before downloading the database.');

    $this->logSubstep('Downloading demo database from ' . static::VORTEX_INSTALL_DEMO_DB_TEST);
    $this->processRun('ahoy download-db', env: [
      'VORTEX_DB_DOWNLOAD_URL' => static::VORTEX_INSTALL_DEMO_DB_TEST,
    ]);
    $this->assertProcessSuccessful();

    $this->assertFileExists('.data/db.sql', 'File .data/db.sql should exist after downloading the database.');

    $this->logStepFinish();
  }

}
