<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\Subtests\SubtestAhoyTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Installer.
 */
class InstallerTest extends FunctionalTestCase {

  use SubtestAhoyTrait;

  protected function setUp(): void {
    parent::setUp();

    static::$sutInstallerEnv = [];

    // For test performance, we only export the current codebase without git
    // history in the parent::setUp(). For these test, though, we need git
    // history to simulate Vortex template repository.
    $this->logSubstep('Prepare Vortex template repository');
    $this->gitInitRepo(static::$repo);
    $this->gitCommitAll(static::$repo, 'Initial Vortex template commit');
    $this->gitAssertClean(static::$repo, 'Git working tree of the Vortex template repository should be clean');
  }

  public function testInstallFromLatest(): void {
    $this->logSubstep('Add custom files to SUT');
    File::dump('test1.txt', 'test content');
    // File resides in directory that is included in Vortex when initialised.
    File::dump('.docker/test2.txt', 'test docker content');
    $this->gitInitRepo(static::$sut);
    $this->gitCommitAll(static::$sut, 'First commit');

    $this->logSubstep('Run Vortex installer to populate SUT with Vortex files');
    $this->runInstaller();
    $this->assertCommonFilesPresent();
    $this->gitCommitAll(static::$sut, 'Init Vortex');

    // Assert that custom files were preserved.
    $this->assertFileExists('test1.txt', 'Custom file should be preserved after Vortex installation');
    $this->assertFileExists('.docker/test2.txt', 'Custom file in Vortex directory should be preserved');
    $this->gitAssertClean(static::$sut, 'SUT git working tree should be clean after Vortex installation');

    $this->logSubstep('Simulate dependencies installation');
    $this->createInstalledDependenciesStub();
    $this->gitCommitAll(static::$sut, 'Added SUT dependencies');

    $this->logSubstep('Adding new commits to Vortex');

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 1 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/.eslintrc.json', "\n# Update 1 to Vortex in .eslintrc.json");
    $latest_installer_commit1 = $this->gitCommitAll(static::$repo, 'Added update 1 to Vortex');
    $this->logNote(sprintf('Update 1 Vortex version commit hash: %s', $latest_installer_commit1));

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 2 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/.eslintrc.json', "\n# Update 2 to Vortex in .eslintrc.json");
    $latest_installer_commit2 = $this->gitCommitAll(static::$repo, 'Added update 2 to Vortex');
    $this->logNote(sprintf('Update 2 Vortex version commit hash: %s', $latest_installer_commit2));

    static::$sutInstallerEnv = [
      // Unset the environment variable that forces using the remote repository
      // in runInstaller().
      'VORTEX_INSTALLER_TEMPLATE_REPO' => FALSE,
      // Do not suppress the installer output so it could be used in assertions.
      'SHELL_VERBOSITY' => FALSE,
    ];
    $this->runInstaller([sprintf('--uri=%s@%s', static::$repo, 'stable')]);
    $this->assertProcessOutputContains(static::$repo);
    $this->assertProcessOutputNotContains($latest_installer_commit1);
    $this->assertProcessOutputNotContains($latest_installer_commit2);
    $this->assertProcessOutputContains('stable');
    $this->gitAssertIsRepository(static::$sut);
    $this->assertCommonFilesPresent(vortex_version: 'develop');

    $this->logSubstep('Assert that committed files were updated');
    $this->assertFileContainsString('docker-compose.yml', '# Update 1 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 1 changes');
    $this->assertFileContainsString('docker-compose.yml', '# Update 2 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 2 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/.eslintrc.json', '# Update 1 to Vortex in .eslintrc.json', 'Theme .eslintrc.json should contain update 1 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/.eslintrc.json', '# Update 2 to Vortex in .eslintrc.json', 'Theme .eslintrc.json should contain update 2 changes');

    $this->logSubstep('Assert that new changes need to be manually resolved');
    $this->gitAssertNotClean(static::$sut, 'Git working tree should not be clean after Vortex update');
  }

  #[Group('p4')]
  public function testInstallFromRef(): void {
    $this->logSubstep('Add custom files to SUT');
    File::dump('test1.txt', 'test content');
    // File resides in directory that is included in Vortex when initialised.
    File::dump('.docker/test2.txt', 'test docker content');
    $this->gitInitRepo(static::$sut);
    $this->gitCommitAll(static::$sut, 'First commit');

    $this->logSubstep('Run Vortex installer to populate SUT with Vortex files');
    $this->runInstaller();
    $this->assertCommonFilesPresent();
    $this->gitCommitAll(static::$sut, 'Init Vortex');

    // Assert that custom files were preserved.
    $this->assertFileExists('test1.txt', 'Custom file should be preserved after Vortex installation');
    $this->assertFileExists('.docker/test2.txt', 'Custom file in Vortex directory should be preserved');
    $this->gitAssertClean(static::$sut, 'SUT git working tree should be clean after Vortex installation');

    $this->logSubstep('Simulate dependencies installation');
    $this->createInstalledDependenciesStub();
    $this->gitCommitAll(static::$sut, 'Added SUT dependencies');

    $this->logSubstep('Adding new commits to Vortex');

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 1 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/.eslintrc.json', "\n# Update 1 to Vortex in .eslintrc.json");
    $latest_installer_commit1 = $this->gitCommitAll(static::$repo, 'Added update 1 to Vortex');
    $this->logNote(sprintf('Update 1 Vortex version commit hash: %s', $latest_installer_commit1));

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 2 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/.eslintrc.json', "\n# Update 2 to Vortex in .eslintrc.json");
    $latest_installer_commit2 = $this->gitCommitAll(static::$repo, 'Added update 2 to Vortex');
    $this->logNote(sprintf('Update 2 Vortex version commit hash: %s', $latest_installer_commit2));

    static::$sutInstallerEnv = [
      // Unset the environment variable that forces using the remote repository
      // in runInstaller().
      'VORTEX_INSTALLER_TEMPLATE_REPO' => FALSE,
      // Do not suppress the installer output so it could be used in assertions.
      'SHELL_VERBOSITY' => FALSE,
    ];
    $this->runInstaller([sprintf('--uri=%s@%s', static::$repo, $latest_installer_commit1)]);
    $this->assertProcessOutputContains(static::$repo);
    $this->assertProcessOutputContains($latest_installer_commit1);
    $this->gitAssertIsRepository(static::$sut);
    $this->assertCommonFilesPresent(vortex_version: $latest_installer_commit1);

    $this->logSubstep('Assert that committed files were updated');
    $this->assertFileContainsString('docker-compose.yml', '# Update 1 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 1 changes');
    $this->assertFileNotContainsString('docker-compose.yml', '# Update 2 to Vortex in docker-compose.yml', 'docker-compose.yml should not contain update 2 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/.eslintrc.json', '# Update 1 to Vortex in .eslintrc.json', 'Theme .eslintrc.json should contain update 1 changes');
    $this->assertFileNotContainsString('web/themes/custom/star_wars/.eslintrc.json', '# Update 2 to Vortex in .eslintrc.json', 'Theme .eslintrc.json should not contain update 2 changes');

    $this->logSubstep('Assert that new changes need to be manually resolved');
    $this->gitAssertNotClean(static::$sut, 'Git working tree should not be clean after Vortex update');
  }

}
