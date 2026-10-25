<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\Subtests\SubtestAhoyTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Vortex CLI.
 */
class CliTest extends FunctionalTestCase {

  use SubtestAhoyTrait;

  protected function setUp(): void {
    parent::setUp();

    static::$sutEnv = [];
    static::$sutPrompts = [];

    // For test performance, we only export the current codebase without git
    // history in the parent::setUp(). For these test, though, we need git
    // history to simulate Vortex template repository.
    $this->logSubstep('Prepare Vortex template repository');
    $this->gitInitRepo(static::$repo);
    $this->gitCommitAll(static::$repo, 'Initial Vortex template commit');
    $this->gitAssertClean(static::$repo, 'Git working tree of the Vortex template repository should be clean');
  }

  #[Group('p4')]
  public function testUpdateFromLatest(): void {
    $this->logSubstep('Add custom files to SUT');
    File::dump('test1.txt', 'test content');
    // File resides in directory that is included in Vortex when initialised.
    File::dump('.docker/test2.txt', 'test docker content');
    $this->gitInitRepo(static::$sut);
    $this->gitCommitAll(static::$sut, 'First commit');

    $this->logSubstep('Run the Vortex CLI to populate SUT with Vortex files');
    $this->runInstall();
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
    $latest_commit1 = $this->gitCommitAll(static::$repo, 'Added update 1 to Vortex');
    $this->logNote(sprintf('Update 1 Vortex version commit hash: %s', $latest_commit1));

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 2 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/eslint.config.mjs', "\n// Update 2 to Vortex in eslint.config.mjs");
    $latest_commit2 = $this->gitCommitAll(static::$repo, 'Added update 2 to Vortex');
    $this->logNote(sprintf('Update 2 Vortex version commit hash: %s', $latest_commit2));

    static::$sutEnv = [
      // Unset the environment variable that forces using the remote repository
      // in runCli().
      'VORTEX_CLI_INSTALL_TEMPLATE_REPO' => FALSE,
      // Do not suppress the CLI output so it could be used in assertions.
      'SHELL_VERBOSITY' => FALSE,
    ];
    $this->runUpdate([sprintf('--uri=%s#%s', static::$repo, 'stable')]);
    $this->assertProcessOutputContains(static::$repo);
    $this->assertProcessOutputNotContains($latest_commit1);
    $this->assertProcessOutputNotContains($latest_commit2);
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
    $this->assertFileExists('scripts/legacy.sh', 'Template-owned script installed into the SUT');
    $this->gitCommitAll(static::$sut, 'Init Vortex');

    $commit_without_script = $this->dropLegacyScriptFromTemplate();

    $this->logSubstep('Update the SUT to the version that no longer ships the script');
    $this->runUpdate([sprintf('--uri=%s#%s', static::$repo, $commit_without_script)]);

    $this->assertFileDoesNotExist('scripts/legacy.sh', 'Unmodified script dropped by the template removed from the SUT');
    $this->assertFileExists('scripts/vortex-tooling.sh', 'Scripts still shipped by the template kept in the SUT');
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
    File::dump(static::$sut . '/scripts/legacy.sh', $modified);
    $this->gitCommitAll(static::$sut, 'Customised the legacy script');

    $commit_without_script = $this->dropLegacyScriptFromTemplate();

    $this->logSubstep('Update the SUT to the version that no longer ships the script');
    $this->runUpdate([sprintf('--uri=%s#%s', static::$repo, $commit_without_script)]);

    $this->assertFileExists('scripts/legacy.sh', 'A script the project modified is never removed');
    $this->assertFileContainsString('scripts/legacy.sh', 'Customised by the project.', 'The project modification is left untouched');
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
      'PROJECT-NOTES.md' => "Project notes.\n",
    ];
    foreach ($project_files as $path => $contents) {
      File::dump(static::$sut . '/' . $path, $contents);
    }
    $this->gitCommitAll(static::$sut, 'Init Vortex with project files');

    $commit_without_script = $this->dropLegacyScriptFromTemplate();

    $this->logSubstep('Update the SUT to the version that no longer ships the script');
    $this->runUpdate([sprintf('--uri=%s#%s', static::$repo, $commit_without_script)]);

    $this->assertFileDoesNotExist('scripts/legacy.sh', 'The template-owned script is still removed');

    foreach ($project_files as $path => $contents) {
      $this->assertFileExists($path, sprintf('Project-authored "%s" kept in the SUT', $path));
      $this->assertFileContainsString($path, trim($contents), sprintf('Project-authored "%s" kept its contents', $path));
    }
  }

  #[Group('p3')]
  public function testUpdateFromRef(): void {
    $this->logSubstep('Add custom files to SUT');
    File::dump('test1.txt', 'test content');
    // File resides in directory that is included in Vortex when initialised.
    File::dump('.docker/test2.txt', 'test docker content');
    $this->gitInitRepo(static::$sut);
    $this->gitCommitAll(static::$sut, 'First commit');

    $this->logSubstep('Run the Vortex CLI to populate SUT with Vortex files');
    $this->runInstall();
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
    $latest_commit1 = $this->gitCommitAll(static::$repo, 'Added update 1 to Vortex');
    $this->logNote(sprintf('Update 1 Vortex version commit hash: %s', $latest_commit1));

    File::append(static::$repo . '/docker-compose.yml', "\n# Update 2 to Vortex in docker-compose.yml");
    File::append(static::$repo . '/web/themes/custom/your_site_theme/eslint.config.mjs', "\n// Update 2 to Vortex in eslint.config.mjs");
    $latest_commit2 = $this->gitCommitAll(static::$repo, 'Added update 2 to Vortex');
    $this->logNote(sprintf('Update 2 Vortex version commit hash: %s', $latest_commit2));

    static::$sutEnv = [
      // Unset the environment variable that forces using the remote repository
      // in runCli().
      'VORTEX_CLI_INSTALL_TEMPLATE_REPO' => FALSE,
      // Do not suppress the CLI output so it could be used in assertions.
      'SHELL_VERBOSITY' => FALSE,
    ];
    $this->runUpdate([sprintf('--uri=%s#%s', static::$repo, $latest_commit1)]);
    $this->assertProcessOutputContains(static::$repo);
    $this->assertProcessOutputContains($latest_commit1);
    $this->gitAssertIsRepository(static::$sut);
    $this->assertCommonFilesPresent(vortex_version: $latest_commit1);

    $this->logSubstep('Assert that committed files were updated');
    $this->assertFileContainsString('docker-compose.yml', '# Update 1 to Vortex in docker-compose.yml', 'docker-compose.yml should contain update 1 changes');
    $this->assertFileNotContainsString('docker-compose.yml', '# Update 2 to Vortex in docker-compose.yml', 'docker-compose.yml should not contain update 2 changes');
    $this->assertFileContainsString('web/themes/custom/star_wars/eslint.config.mjs', '// Update 1 to Vortex in eslint.config.mjs', 'Theme eslint.config.mjs should contain update 1 changes');
    $this->assertFileNotContainsString('web/themes/custom/star_wars/eslint.config.mjs', '// Update 2 to Vortex in eslint.config.mjs', 'Theme eslint.config.mjs should not contain update 2 changes');

    $this->logSubstep('Assert that new changes need to be manually resolved');
    $this->gitAssertNotClean(static::$sut, 'Git working tree should not be clean after Vortex update');
  }

  /**
   * Add a template-owned script to the template repository.
   */
  protected function addLegacyScriptToTemplate(): string {
    $this->logSubstep('Add a template-owned script to the Vortex template repository');
    File::dump(static::$repo . '/scripts/legacy.sh', "#!/usr/bin/env bash\necho 'Legacy script.'\n");
    $commit = $this->gitCommitAll(static::$repo, 'Added a legacy script to Vortex');
    $this->logNote(sprintf('Vortex version with the script: %s', $commit));

    return $commit;
  }

  /**
   * Drop the template-owned script from the template repository.
   */
  protected function dropLegacyScriptFromTemplate(): string {
    $this->logSubstep('Drop the script from the Vortex template repository');
    File::remove(static::$repo . '/scripts/legacy.sh');
    $commit = $this->gitCommitAll(static::$repo, 'Removed the legacy script from Vortex');
    $this->logNote(sprintf('Vortex version without the script: %s', $commit));

    return $commit;
  }

  /**
   * Install the SUT from a given template reference.
   */
  protected function installSutFrom(string $ref): void {
    $this->gitInitRepo(static::$sut);

    static::$sutEnv = [
      'VORTEX_CLI_INSTALL_TEMPLATE_REPO' => FALSE,
      'SHELL_VERBOSITY' => FALSE,
    ];
    $this->runInstall([sprintf('--uri=%s#%s', static::$repo, $ref)]);
  }

}
