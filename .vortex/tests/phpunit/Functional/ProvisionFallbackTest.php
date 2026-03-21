<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use DrevOps\Vortex\Tests\Traits\Subtests\SubtestAhoyTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests provision fallback to profile when database dump is not available.
 */
class ProvisionFallbackTest extends FunctionalTestCase {

  use SubtestAhoyTrait;

  protected function setUp(): void {
    parent::setUp();

    static::$sutInstallerEnv = [];

    $this->dockerCleanup();
  }

  #[Group('p0')]
  public function testProvisionFallbackToProfile(): void {
    static::$sutInstallerEnv = ['VORTEX_INSTALLER_IS_DEMO' => '1'];
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Build the site with database dump');
    $this->subtestAhoyBuild();

    $this->logSubstep('Export configuration from the provisioned site');
    $this->cmd('ahoy drush cex -y', '* ../config/default', 'Export configuration should complete successfully');
    $this->syncToHost('config');
    $this->assertFilesWildcardExists('config/default/*.yml');

    $this->logSubstep('Remove the database dump file');
    $this->removePathHostAndContainer('.data/db.sql');
    $this->assertFileDoesNotExist('.data/db.sql', 'Database dump file should not exist after removal');

    $this->logSubstep('Drop the database to simulate a fresh environment');
    $this->cmd('ahoy drush sql:drop -y', txt: 'Database should be dropped successfully');

    $this->logSubstep('Provision without fallback should fail');
    $this->fileAddVar('.env', 'VORTEX_PROVISION_FALLBACK_TO_PROFILE', 0);
    $this->syncToContainer(['.env']);
    $this->cmdFail(
      'ahoy provision',
      [
        '* Unable to import database from file',
        '* does not exist',
        '* Site content was not changed',
      ],
      'Provision without fallback should fail when no database dump is available',
    );

    $this->logSubstep('Provision with fallback should succeed');
    $this->fileAddVar('.env', 'VORTEX_PROVISION_FALLBACK_TO_PROFILE', 1);
    $this->syncToContainer(['.env']);
    $this->cmd(
      'ahoy provision',
      [
        '* Database dump file is not available. Falling back to profile installation',
        '* Installed a site from the profile',
        '* Removing entities and config created by the profile to prevent conflicts during configuration import',
        '* Importing configuration',
        '* Completed configuration import',
        '* Running deployment hooks',
      ],
      'Provision with fallback should complete successfully',
      tio: 15 * 60,
    );

    $this->logSubstep('Assert that required modules are enabled');
    $this->cmd('ahoy drush pm:list --status=enabled --type=module --format=list', '* ys_demo', 'ys_demo module should be enabled after fallback provision');

    $this->logSubstep('Assert that homepage does not contain database dump content');
    $this->assertWebpageNotContains('/', 'This demo page is sourced from the Vortex database dump file', 'Homepage should not show database dump content after fallback provision');

    $this->logSubstep('Assert that homepage is accessible');
    $this->assertWebpageContains('/', '<html', 'Homepage should be a valid HTML page');
  }

}
