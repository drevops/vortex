<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('deploy')]
#[RunTestsInSeparateProcesses]
class DeployArtifactTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    $this->envSetMultiple([
      'VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE' => 'git@github.com:org/repo.git',
      'VORTEX_DEPLOY_ARTIFACT_GIT_USER_NAME' => 'Deploy Bot',
      'VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL' => 'deploy@example.com',
      'VORTEX_DEPLOY_ARTIFACT_SRC' => self::$tmp . '/src',
      'VORTEX_DEPLOY_ARTIFACT_ROOT' => self::$tmp . '/root',
      'HOME' => self::$tmp,
    ]);

    // Create required directories.
    mkdir(self::$tmp . '/src', 0755, TRUE);
    mkdir(self::$tmp . '/root', 0755, TRUE);
    mkdir(self::$tmp . '/root/.git', 0755, TRUE);
    file_put_contents(self::$tmp . '/root/.gitignore.artifact', "vendor/\n");
  }

  public function testMissingGitRemote(): void {
    $this->envUnset('VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE');

    $this->runScriptError('src/deploy-artifact', 'Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE');
  }

  public function testMissingGitUserEmail(): void {
    $this->envUnset('VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL');

    $this->runScriptError('src/deploy-artifact', 'Missing required value for VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL');
  }

  public function testMissingSrc(): void {
    $this->envUnset('VORTEX_DEPLOY_ARTIFACT_SRC');

    $this->runScriptError('src/deploy-artifact', 'Missing required value for VORTEX_DEPLOY_ARTIFACT_SRC');
  }

  public function testDefaultValues(): void {
    // Mock shell_exec for git config checks.
    $this->mockShellExecMultiple([
      ['value' => 'Existing User'],
      ['value' => 'existing@example.com'],
    ]);

    // Mock setup-ssh.
    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    // Mock composer install.
    $this->mockPassthru([
      'cmd' => 'composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs drevops/git-artifact:~1.1',
      'output' => 'Installing git-artifact',
      'result_code' => 0,
    ]);

    // Mock git-artifact command.
    $git_artifact_cmd = sprintf(
      '%s %s --root=%s --src=%s --branch=%s --gitignore=%s --log=%s -vvv',
      escapeshellarg(self::$tmp . '/.composer/vendor/bin/git-artifact'),
      escapeshellarg('git@github.com:org/repo.git'),
      escapeshellarg(self::$tmp . '/root'),
      escapeshellarg(self::$tmp . '/src'),
      escapeshellarg('[branch]'),
      escapeshellarg(self::$tmp . '/src/.gitignore.artifact'),
      escapeshellarg(self::$tmp . '/root/deployment_log.txt')
    );

    $this->mockPassthru([
      'cmd' => $git_artifact_cmd,
      'output' => 'Artifact deployed',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-artifact');

    $this->assertStringContainsString('Started ARTIFACT deployment.', $output);
    $this->assertStringContainsString('Installing artifact builder.', $output);
    $this->assertStringContainsString('Copying git repo files meta file to the deploy code repo.', $output);
    $this->assertStringContainsString('Running artifact builder.', $output);
    $this->assertStringContainsString('Finished ARTIFACT deployment.', $output);
  }

  public function testConfigureGitUser(): void {
    // Mock shell_exec for git config checks (empty values).
    $this->mockShellExecMultiple([
      ['value' => ''],
      ['value' => ''],
    ]);

    // Mock git config commands.
    $this->mockPassthru([
      'cmd' => 'git config --global user.name ' . escapeshellarg('Deploy Bot'),
      'output' => '',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => 'git config --global user.email ' . escapeshellarg('deploy@example.com'),
      'output' => '',
      'result_code' => 0,
    ]);

    // Mock setup-ssh.
    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    // Mock composer install.
    $this->mockPassthru([
      'cmd' => 'composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs drevops/git-artifact:~1.1',
      'output' => 'Installing git-artifact',
      'result_code' => 0,
    ]);

    // Mock git-artifact command.
    $git_artifact_cmd = sprintf(
      '%s %s --root=%s --src=%s --branch=%s --gitignore=%s --log=%s -vvv',
      escapeshellarg(self::$tmp . '/.composer/vendor/bin/git-artifact'),
      escapeshellarg('git@github.com:org/repo.git'),
      escapeshellarg(self::$tmp . '/root'),
      escapeshellarg(self::$tmp . '/src'),
      escapeshellarg('[branch]'),
      escapeshellarg(self::$tmp . '/src/.gitignore.artifact'),
      escapeshellarg(self::$tmp . '/root/deployment_log.txt')
    );

    $this->mockPassthru([
      'cmd' => $git_artifact_cmd,
      'output' => 'Artifact deployed',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-artifact');

    $this->assertStringContainsString('Configuring global git user name.', $output);
    $this->assertStringContainsString('Configuring global git user email.', $output);
  }

  public function testGitConfigUserNameFailure(): void {
    // Mock shell_exec for git config check (empty value -
    // only one check before failure).
    $this->mockShellExec('');

    // Mock git config user.name command failure.
    $this->mockPassthru([
      'cmd' => 'git config --global user.name ' . escapeshellarg('Deploy Bot'),
      'output' => 'git config failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-artifact');
  }

  public function testGitConfigUserEmailFailure(): void {
    // Mock shell_exec for git config checks (empty values).
    $this->mockShellExecMultiple([
      ['value' => ''],
      ['value' => ''],
    ]);

    // Mock git config user.name command success.
    $this->mockPassthru([
      'cmd' => 'git config --global user.name ' . escapeshellarg('Deploy Bot'),
      'output' => '',
      'result_code' => 0,
    ]);

    // Mock git config user.email command failure.
    $this->mockPassthru([
      'cmd' => 'git config --global user.email ' . escapeshellarg('deploy@example.com'),
      'output' => 'git config failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-artifact');
  }

  public function testSshSetupFailure(): void {
    // Mock shell_exec for git config checks.
    $this->mockShellExecMultiple([
      ['value' => 'Existing User'],
      ['value' => 'existing@example.com'],
    ]);

    // Mock setup-ssh failure.
    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-artifact');
  }

  public function testComposerInstallFailure(): void {
    // Mock shell_exec for git config checks.
    $this->mockShellExecMultiple([
      ['value' => 'Existing User'],
      ['value' => 'existing@example.com'],
    ]);

    // Mock setup-ssh.
    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    // Mock composer install failure.
    $this->mockPassthru([
      'cmd' => 'composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs drevops/git-artifact:~1.1',
      'output' => 'Composer install failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-artifact');
  }

  public function testGitArtifactFailure(): void {
    // Mock shell_exec for git config checks.
    $this->mockShellExecMultiple([
      ['value' => 'Existing User'],
      ['value' => 'existing@example.com'],
    ]);

    // Mock setup-ssh.
    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    // Mock composer install.
    $this->mockPassthru([
      'cmd' => 'composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs drevops/git-artifact:~1.1',
      'output' => 'Installing git-artifact',
      'result_code' => 0,
    ]);

    // Mock git-artifact command failure.
    $git_artifact_cmd = sprintf(
      '%s %s --root=%s --src=%s --branch=%s --gitignore=%s --log=%s -vvv',
      escapeshellarg(self::$tmp . '/.composer/vendor/bin/git-artifact'),
      escapeshellarg('git@github.com:org/repo.git'),
      escapeshellarg(self::$tmp . '/root'),
      escapeshellarg(self::$tmp . '/src'),
      escapeshellarg('[branch]'),
      escapeshellarg(self::$tmp . '/src/.gitignore.artifact'),
      escapeshellarg(self::$tmp . '/root/deployment_log.txt')
    );

    $this->mockPassthru([
      'cmd' => $git_artifact_cmd,
      'output' => 'Artifact deployment failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-artifact');
  }

  public function testRealpathFailure(): void {
    // Set non-existent paths to trigger realpath failure.
    $this->envSet('VORTEX_DEPLOY_ARTIFACT_SRC', self::$tmp . '/nonexistent-src');
    $this->envSet('VORTEX_DEPLOY_ARTIFACT_ROOT', self::$tmp . '/nonexistent-root');

    // Mock shell_exec for git config checks.
    $this->mockShellExecMultiple([
      ['value' => 'Existing User'],
      ['value' => 'existing@example.com'],
    ]);

    // Mock setup-ssh.
    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    // Mock composer install.
    $this->mockPassthru([
      'cmd' => 'composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs drevops/git-artifact:~1.1',
      'output' => 'Installing git-artifact',
      'result_code' => 0,
    ]);

    $this->runScriptError('src/deploy-artifact', 'Failed to resolve real path for deployment directories.');
  }

  public function testCustomBranch(): void {
    $this->envSet('VORTEX_DEPLOY_ARTIFACT_DST_BRANCH', 'production');

    // Mock shell_exec for git config checks.
    $this->mockShellExecMultiple([
      ['value' => 'Existing User'],
      ['value' => 'existing@example.com'],
    ]);

    // Mock setup-ssh.
    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    // Mock composer install.
    $this->mockPassthru([
      'cmd' => 'composer global require --dev -n --ansi --prefer-source --ignore-platform-reqs drevops/git-artifact:~1.1',
      'output' => 'Installing git-artifact',
      'result_code' => 0,
    ]);

    // Mock git-artifact command with custom branch.
    $git_artifact_cmd = sprintf(
      '%s %s --root=%s --src=%s --branch=%s --gitignore=%s --log=%s -vvv',
      escapeshellarg(self::$tmp . '/.composer/vendor/bin/git-artifact'),
      escapeshellarg('git@github.com:org/repo.git'),
      escapeshellarg(self::$tmp . '/root'),
      escapeshellarg(self::$tmp . '/src'),
      escapeshellarg('production'),
      escapeshellarg(self::$tmp . '/src/.gitignore.artifact'),
      escapeshellarg(self::$tmp . '/root/deployment_log.txt')
    );

    $this->mockPassthru([
      'cmd' => $git_artifact_cmd,
      'output' => 'Artifact deployed',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-artifact');

    $this->assertStringContainsString('Finished ARTIFACT deployment.', $output);
  }

  protected function getSetupSshPath(): string {
    return dirname(__DIR__, 2) . '/src/setup-ssh';
  }

}
