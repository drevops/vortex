<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Installer.
 */
class InstallerTest extends FunctionalTestCase {

  protected function setUp(): void {
    parent::setUp();

    static::$sutInstallerEnv = [];
    static::$sutInstallerPrompts = [];

    // For test performance, we only export the current codebase without git
    // history in the parent::setUp(). For these test, though, we need git
    // history to simulate Vortex template repository.
    $this->logSubstep('Prepare Vortex template repository');
    $this->gitInitRepo(static::$repo);
    $this->gitCommitAll(static::$repo, 'Initial Vortex template commit');
    $this->gitAssertClean(static::$repo, 'Git working tree of the Vortex template repository should be clean');
  }

  #[Group('p4')]
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
    File::append(static::$repo . '/web/themes/custom/your_site_theme/eslint.config.mjs', "\n// Update 1 to Vortex in eslint.config.mjs");
    $latest_installer_commit1 = $this->gitCommitAll(static::$repo, 'Added update 1 to Vortex');
    $this->logNote(sprintf('Update 1 Vortex version commit hash: %s', $latest_installer_commit1));

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 2 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/eslint.config.mjs', "\n// Update 2 to Vortex in eslint.config.mjs");
    $latest_installer_commit2 = $this->gitCommitAll(static::$repo, 'Added update 2 to Vortex');
    $this->logNote(sprintf('Update 2 Vortex version commit hash: %s', $latest_installer_commit2));

    static::$sutInstallerEnv = [
      // Unset the environment variable that forces using the remote repository
      // in runInstaller().
      'VORTEX_INSTALLER_TEMPLATE_REPO' => FALSE,
      // Do not suppress the installer output so it could be used in assertions.
      'SHELL_VERBOSITY' => FALSE,
    ];
    $this->runInstaller([sprintf('--uri=%s#%s', static::$repo, 'stable')]);
    $this->assertProcessOutputContains(static::$repo);
    $this->assertProcessOutputNotContains($latest_installer_commit1);
    $this->assertProcessOutputNotContains($latest_installer_commit2);
    $this->assertProcessOutputContains('stable');
    $this->gitAssertIsRepository(static::$sut);
    $this->assertCommonFilesPresent(vortex_version: 'develop');

    $this->logSubstep('Assert that committed files were updated');
    $this->assertFileContainsString('docker-compose.yml', '# Update 1 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 1 changes');
    $this->assertFileContainsString('docker-compose.yml', '# Update 2 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 2 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/eslint.config.mjs', '// Update 1 to Vortex in eslint.config.mjs', 'Theme eslint.config.mjs should contain update 1 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/eslint.config.mjs', '// Update 2 to Vortex in eslint.config.mjs', 'Theme eslint.config.mjs should contain update 2 changes');

    $this->logSubstep('Assert that new changes need to be manually resolved');
    $this->gitAssertNotClean(static::$sut, 'Git working tree should not be clean after Vortex update');
  }

  #[Group('p3')]
  public function testUpdateRemovesUnmodifiedFilesDroppedByTemplate(): void {
    $commit_with_script = $this->addLegacyScriptToTemplate();

    $this->logSubstep('Install the SUT from the version that ships the script');
    $this->installSutFrom($commit_with_script);
    $this->assertFileExists('scripts/provision-50-legacy.sh', 'Template-owned script installed into the SUT');
    $this->assertFileExists('.vortex-manifest.json', 'Install records what it wrote');
    $this->gitCommitAll(static::$sut, 'Init Vortex');

    $commit_without_script = $this->dropLegacyScriptFromTemplate();

    $this->logSubstep('Update the SUT to the version that no longer ships the script');
    $this->runInstaller([sprintf('--uri=%s#%s', static::$repo, $commit_without_script)]);

    $this->assertFileDoesNotExist('scripts/provision-50-legacy.sh', 'Unmodified script dropped by the template removed from the SUT');
    $this->assertFileExists('scripts/provision-40-example.sh', 'Scripts still shipped by the template kept in the SUT');
    $this->assertFileExists('scripts/README.md', 'Sibling shipped files kept in the SUT');
  }

  #[Group('p3')]
  public function testUpdateKeepsModifiedFilesDroppedByTemplate(): void {
    $commit_with_script = $this->addLegacyScriptToTemplate();

    $this->logSubstep('Install the SUT from the version that ships the script');
    $this->installSutFrom($commit_with_script);
    $this->gitCommitAll(static::$sut, 'Init Vortex');

    $this->logSubstep('Modify the script in the SUT, as a project would');
    $modified = "#!/usr/bin/env bash\necho 'Customised by the project.'\n";
    File::dump(static::$sut . '/scripts/provision-50-legacy.sh', $modified);
    $this->gitCommitAll(static::$sut, 'Customised the provision script');

    $commit_without_script = $this->dropLegacyScriptFromTemplate();

    $this->logSubstep('Update the SUT to the version that no longer ships the script');
    $this->runInstaller([sprintf('--uri=%s#%s', static::$repo, $commit_without_script)]);

    $this->assertFileExists('scripts/provision-50-legacy.sh', 'A script the project modified is never removed');
    $this->assertFileContainsString('scripts/provision-50-legacy.sh', 'Customised by the project.', 'The project modification is left untouched');
  }

  #[Group('p3')]
  public function testUpdateKeepsProjectAuthoredFiles(): void {
    $commit_with_script = $this->addLegacyScriptToTemplate();

    $this->logSubstep('Install the SUT from the version that ships the script');
    $this->installSutFrom($commit_with_script);

    $this->logSubstep('Add project-authored files where projects extend Vortex');
    $project_files = [
      'scripts/custom-deploy.sh' => "#!/usr/bin/env bash\necho 'Project deploy.'\n",
      '.docker/custom.dockerfile' => "FROM alpine\n",
      '.github/workflows/custom.yml' => "name: Custom\n",
      '.circleci/custom.yml' => "version: 2.1\n",
      'config/custom.settings.yml' => "custom: true\n",
      'recipes/custom/recipe.yml' => "name: Custom recipe\n",
      '.claude/skills/custom/SKILL.md' => "# Custom skill\n",
      '.claude/settings.local.json' => "{\"permissions\": {}}\n",
      'PROJECT-NOTES.md' => "Project notes.\n",
    ];
    foreach ($project_files as $path => $contents) {
      File::dump(static::$sut . '/' . $path, $contents);
    }
    $this->gitCommitAll(static::$sut, 'Init Vortex with project files');

    $this->logSubstep('Assert that the shipped ignore rules share skills and keep other ".claude/" files local');
    $this->gitAssertFilesTracked('.claude/skills/custom/SKILL.md', static::$sut);
    $this->gitAssertFilesNotTracked('.claude/settings.local.json', static::$sut);

    $commit_without_script = $this->dropLegacyScriptFromTemplate();

    $this->logSubstep('Update the SUT to the version that no longer ships the script');
    $this->runInstaller([sprintf('--uri=%s#%s', static::$repo, $commit_without_script)]);

    $this->assertFileDoesNotExist('scripts/provision-50-legacy.sh', 'The template-owned script is still removed');

    foreach ($project_files as $path => $contents) {
      $this->assertFileExists($path, sprintf('Project-authored "%s" kept in the SUT', $path));
      $this->assertFileContainsString($path, trim($contents), sprintf('Project-authored "%s" kept its contents', $path));
    }
  }

  /**
   * Add a template-owned script to the template repository.
   */
  protected function addLegacyScriptToTemplate(): string {
    $this->logSubstep('Add a template-owned script to the Vortex template repository');
    File::dump(static::$repo . '/scripts/provision-50-legacy.sh', "#!/usr/bin/env bash\necho 'Legacy provision step.'\n");
    $commit = $this->gitCommitAll(static::$repo, 'Added a legacy provision script to Vortex');
    $this->logNote(sprintf('Vortex version with the script: %s', $commit));

    return $commit;
  }

  /**
   * Drop the template-owned script from the template repository.
   */
  protected function dropLegacyScriptFromTemplate(): string {
    $this->logSubstep('Drop the script from the Vortex template repository');
    File::remove(static::$repo . '/scripts/provision-50-legacy.sh');
    $commit = $this->gitCommitAll(static::$repo, 'Removed the legacy provision script from Vortex');
    $this->logNote(sprintf('Vortex version without the script: %s', $commit));

    return $commit;
  }

  /**
   * Install the SUT from a given template reference.
   */
  protected function installSutFrom(string $ref): void {
    $this->gitInitRepo(static::$sut);
    // The shipped '.gitignore' is the only ignore source these tests assert
    // on, so the developer's global excludes file must not reach the SUT.
    $this->gitDisableGlobalExcludes(static::$sut);

    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_TEMPLATE_REPO' => FALSE,
      'SHELL_VERBOSITY' => FALSE,
    ];
    $this->runInstaller([sprintf('--uri=%s#%s', static::$repo, $ref)]);
  }

  #[Group('p3')]
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
    File::append(static::$repo . '/web/themes/custom/your_site_theme/eslint.config.mjs', "\n// Update 1 to Vortex in eslint.config.mjs");
    $latest_installer_commit1 = $this->gitCommitAll(static::$repo, 'Added update 1 to Vortex');
    $this->logNote(sprintf('Update 1 Vortex version commit hash: %s', $latest_installer_commit1));

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 2 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/eslint.config.mjs', "\n// Update 2 to Vortex in eslint.config.mjs");
    $latest_installer_commit2 = $this->gitCommitAll(static::$repo, 'Added update 2 to Vortex');
    $this->logNote(sprintf('Update 2 Vortex version commit hash: %s', $latest_installer_commit2));

    static::$sutInstallerEnv = [
      // Unset the environment variable that forces using the remote repository
      // in runInstaller().
      'VORTEX_INSTALLER_TEMPLATE_REPO' => FALSE,
      // Do not suppress the installer output so it could be used in assertions.
      'SHELL_VERBOSITY' => FALSE,
    ];
    $this->runInstaller([sprintf('--uri=%s#%s', static::$repo, $latest_installer_commit1)]);
    $this->assertProcessOutputContains(static::$repo);
    $this->assertProcessOutputContains($latest_installer_commit1);
    $this->gitAssertIsRepository(static::$sut);
    $this->assertCommonFilesPresent(vortex_version: $latest_installer_commit1);

    $this->logSubstep('Assert that committed files were updated');
    $this->assertFileContainsString('docker-compose.yml', '# Update 1 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 1 changes');
    $this->assertFileNotContainsString('docker-compose.yml', '# Update 2 to Vortex in docker-compose.yml', 'docker-compose.yml should not contain update 2 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/eslint.config.mjs', '// Update 1 to Vortex in eslint.config.mjs', 'Theme eslint.config.mjs should contain update 1 changes');
    $this->assertFileNotContainsString('web/themes/custom/star_wars/eslint.config.mjs', '// Update 2 to Vortex in eslint.config.mjs', 'Theme eslint.config.mjs should not contain update 2 changes');

    $this->logSubstep('Assert that new changes need to be manually resolved');
    $this->gitAssertNotClean(static::$sut, 'Git working tree should not be clean after Vortex update');
  }

}
