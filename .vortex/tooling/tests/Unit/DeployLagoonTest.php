<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('deploy')]
#[RunTestsInSeparateProcesses]
class DeployLagoonTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../src/helpers.php';

    // Create SSH key file that the script expects.
    mkdir(self::$tmp . '/.ssh', 0700, TRUE);
    file_put_contents(self::$tmp . '/.ssh/id_rsa', 'test-key');

    $this->envSetMultiple([
      'LAGOON_PROJECT' => 'test-project',
      'VORTEX_DEPLOY_BRANCH' => 'develop',
      'HOME' => self::$tmp,
      // String-based variables.
      'VORTEX_DEPLOY_PR' => '',
      'VORTEX_DEPLOY_PR_HEAD' => '',
      'VORTEX_DEPLOY_SSH_FINGERPRINT' => '',
      'VORTEX_SSH_FINGERPRINT' => '',
      // Boolean-based variables.
      'VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED' => '0',
      'VORTEX_LAGOONCLI_FORCE_INSTALL' => '0',
    ]);
  }

  public function testMissingLagoonProject(): void {
    // Set branch to non-empty to pass the branch/PR check.
    $this->envSet('VORTEX_DEPLOY_BRANCH', 'develop');
    $this->envUnset('LAGOON_PROJECT');

    $this->runScriptError('src/deploy-lagoon', 'Missing required value for VORTEX_DEPLOY_LAGOON_PROJECT, LAGOON_PROJECT');
  }

  public function testMissingBranchAndPr(): void {
    $this->envSet('VORTEX_DEPLOY_BRANCH', '');
    $this->envSet('VORTEX_DEPLOY_PR', '');

    $this->runScriptError('src/deploy-lagoon', 'Missing required value for VORTEX_DEPLOY_BRANCH or VORTEX_DEPLOY_PR');
  }

  public function testDestroyAction(): void {
    $this->envSet('VORTEX_DEPLOY_ACTION', 'destroy');

    // Create a fake lagoon binary to satisfy command_exists check.
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("delete environment --environment 'develop'"),
      'output' => 'Environment deleted',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Started LAGOON deployment.', $output);
    $this->assertStringContainsString('Destroying environment: project test-project, branch: develop.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testBranchFreshDeployment(): void {
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"main","deploytype":"branch"}]}',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy branch --branch 'develop'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Started LAGOON deployment.', $output);
    $this->assertStringContainsString('Discovering existing environments for branch deployments.', $output);
    $this->assertStringContainsString('Deploying environment: project test-project, branch: develop.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testBranchRedeployment(): void {
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"develop","deploytype":"branch"}]}',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("update variable --environment 'develop' --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"),
      'output' => 'Variable updated',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy latest --environment 'develop'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Found already deployed environment for branch "develop".', $output);
    $this->assertStringContainsString('Setting a DB overwrite flag to 0.', $output);
    $this->assertStringContainsString('Redeploying environment: project test-project, branch: develop.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testBranchRedeploymentWithDbOverride(): void {
    $this->envSet('VORTEX_DEPLOY_ACTION', 'deploy_override_db');
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"develop","deploytype":"branch"}]}',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("update variable --environment 'develop' --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"),
      'output' => 'Variable updated',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("update variable --environment 'develop' --name VORTEX_PROVISION_OVERRIDE_DB --value 1 --scope global"),
      'output' => 'Variable updated',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy latest --environment 'develop'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("update variable --environment 'develop' --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"),
      'output' => 'Variable updated',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Adding a DB import override flag for the current deployment.', $output);
    $this->assertStringContainsString('Waiting for deployment to be queued.', $output);
    $this->assertStringContainsString('Removing a DB import override flag for the current deployment.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testPrFreshDeployment(): void {
    $this->envSetMultiple([
      'VORTEX_DEPLOY_PR' => '123',
      'VORTEX_DEPLOY_PR_HEAD' => 'abc123',
      'VORTEX_DEPLOY_BRANCH' => 'feature-branch',
    ]);
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"pr-456","deploytype":"pullrequest"}]}',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy pullrequest --number '123' --base-branch-name 'develop' --base-branch-ref 'origin/develop' --head-branch-name 'feature-branch' --head-branch-ref 'abc123' --title 'pr-123'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Started LAGOON deployment.', $output);
    $this->assertStringContainsString('Discovering existing environments for pullrequest deployments.', $output);
    $this->assertStringContainsString('Deploying environment: project test-project, PR: 123.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testPrRedeployment(): void {
    $this->envSetMultiple([
      'VORTEX_DEPLOY_PR' => '123',
      'VORTEX_DEPLOY_PR_HEAD' => 'abc123',
      'VORTEX_DEPLOY_BRANCH' => 'feature-branch',
    ]);
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"pr-123","deploytype":"pullrequest"}]}',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("update variable --environment 'pr-123' --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"),
      'output' => 'Variable updated',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy pullrequest --number '123' --base-branch-name 'develop' --base-branch-ref 'origin/develop' --head-branch-name 'feature-branch' --head-branch-ref 'abc123' --title 'pr-123'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Found already deployed environment for PR "123".', $output);
    $this->assertStringContainsString('Setting a DB overwrite flag to 0.', $output);
    $this->assertStringContainsString('Redeploying environment: project test-project, PR: 123.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testEnvironmentLimitExceeded(): void {
    $this->envSet('VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED', '0');
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"main","deploytype":"branch"}]}',
      'result_code' => 0,
    ]);

    // Deploy succeeds but output contains "exceed" warning.
    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy branch --branch 'develop'"),
      'output' => 'Warning: This deployment would exceed the environment limit',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Lagoon environment limit exceeded.', $output);
    $this->assertStringContainsString('Ignoring environment limit exceeded error as per configuration.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testSshSetupFailure(): void {
    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-lagoon');
  }

  public function testLagoonConfigFailure(): void {
    $this->createFakeLagoonBinary();

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config failed',
      'result_code' => 1,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->runScript('src/deploy-lagoon');
  }

  public function testLagoonCliCommandFailure(): void {
    $this->createFakeLagoonBinary();

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => 'Error: Failed to list environments',
      'result_code' => 2,
    ]);

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('[FAIL] Lagoon CLI command', $output);
  }

  public function testLagoonInvalidJsonResponse(): void {
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    // List environments returns invalid JSON.
    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => 'not valid json',
      'result_code' => 0,
    ]);

    // Fresh deployment since no existing environments found (invalid JSON
    // returns empty array).
    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy branch --branch 'develop'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Deploying environment: project test-project, branch: develop.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testEnvironmentLimitExceededWithFailFlag(): void {
    $this->envSet('VORTEX_DEPLOY_LAGOON_FAIL_ENV_LIMIT_EXCEEDED', '1');
    $this->createFakeLagoonBinary();
    // Set GLOBALS after createFakeLagoonBinary() since setLagoonGlobals()
    // resets it.
    // The script's function uses 'global' keyword to access this value.
    $GLOBALS['deploy_lagoon_fail_when_env_limit_exceeded'] = TRUE;

    $this->mockQuit(1);

    $this->expectException(QuitErrorException::class);
    $this->expectExceptionCode(1);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"main","deploytype":"branch"}]}',
      'result_code' => 0,
    ]);

    // Deploy returns output containing "exceed".
    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy branch --branch 'develop'"),
      'output' => 'Warning: This deployment would exceed the environment limit',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Lagoon environment limit exceeded.', $output);
  }

  public function testPrRedeploymentWithDbOverride(): void {
    $this->envSetMultiple([
      'VORTEX_DEPLOY_PR' => '123',
      'VORTEX_DEPLOY_PR_HEAD' => 'abc123',
      'VORTEX_DEPLOY_BRANCH' => 'feature-branch',
      'VORTEX_DEPLOY_ACTION' => 'deploy_override_db',
    ]);
    $this->createFakeLagoonBinary();

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonConfigAddCommand(),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand('list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"pr-123","deploytype":"pullrequest"}]}',
      'result_code' => 0,
    ]);

    // Set DB overwrite flag to 0.
    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("update variable --environment 'pr-123' --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"),
      'output' => 'Variable updated',
      'result_code' => 0,
    ]);

    // Set DB overwrite flag to 1 for this deployment.
    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("update variable --environment 'pr-123' --name VORTEX_PROVISION_OVERRIDE_DB --value 1 --scope global"),
      'output' => 'Variable updated',
      'result_code' => 0,
    ]);

    // Deploy PR.
    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("deploy pullrequest --number '123' --base-branch-name 'develop' --base-branch-ref 'origin/develop' --head-branch-name 'feature-branch' --head-branch-ref 'abc123' --title 'pr-123'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    // Reset DB overwrite flag after deployment.
    $this->mockPassthru([
      'cmd' => $this->getLagoonCommand("update variable --environment 'pr-123' --name VORTEX_PROVISION_OVERRIDE_DB --value 0 --scope global"),
      'output' => 'Variable updated',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Found already deployed environment for PR "123".', $output);
    $this->assertStringContainsString('Adding a DB import override flag for the current deployment.', $output);
    $this->assertStringContainsString('Redeploying environment: project test-project, PR: 123.', $output);
    $this->assertStringContainsString('Waiting for deployment to be queued.', $output);
    $this->assertStringContainsString('Removing a DB import override flag for the current deployment.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  public function testLagoonCliDownloadFailure(): void {
    $this->envSet('VORTEX_LAGOONCLI_FORCE_INSTALL', '1');
    $this->envSet('VORTEX_LAGOONCLI_PATH', self::$tmp);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $platform = strtolower(php_uname('s'));
    $arch = str_replace(['x86_64', 'aarch64'], ['amd64', 'arm64'], php_uname('m'));
    $download_url = sprintf(
      'https://github.com/uselagoon/lagoon-cli/releases/download/v0.32.0/lagoon-cli-v0.32.0-%s-%s',
      $platform,
      $arch
    );

    $this->mockRequestGet(
      $download_url,
      [],
      60,
      ['status' => 404, 'ok' => FALSE, 'body' => FALSE, 'error' => 'Not Found']
    );

    $this->runScriptError('src/deploy-lagoon', 'Failed to download Lagoon CLI from');
  }

  public function testLagoonCliInstallationCreatesDirectory(): void {
    $this->envSet('VORTEX_LAGOONCLI_FORCE_INSTALL', '1');
    // Use a path that doesn't exist yet.
    $cli_path = self::$tmp . '/lagoon_cli_dir';
    $this->envSet('VORTEX_LAGOONCLI_PATH', $cli_path);

    $lagoon_bin = $cli_path . '/lagoon';
    $this->setLagoonGlobals($lagoon_bin);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $platform = strtolower(php_uname('s'));
    $arch = str_replace(['x86_64', 'aarch64'], ['amd64', 'arm64'], php_uname('m'));
    $download_url = sprintf(
      'https://github.com/uselagoon/lagoon-cli/releases/download/v0.32.0/lagoon-cli-v0.32.0-%s-%s',
      $platform,
      $arch
    );

    $this->mockRequestGet(
      $download_url,
      [],
      60,
      ['status' => 200, 'ok' => TRUE, 'body' => 'fake binary content']
    );

    $this->mockPassthru([
      'cmd' => sprintf("'%s' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'", $lagoon_bin),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommandWithPath($lagoon_bin, 'list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"main","deploytype":"branch"}]}',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommandWithPath($lagoon_bin, "deploy branch --branch 'develop'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Installing Lagoon CLI.', $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);

    // Verify directory was created.
    $this->assertDirectoryExists($cli_path);
  }

  public function testLagoonCliInstallation(): void {
    $this->envSet('VORTEX_LAGOONCLI_FORCE_INSTALL', '1');
    $this->envSet('VORTEX_LAGOONCLI_PATH', self::$tmp);

    $lagoon_bin = self::$tmp . '/lagoon';
    $this->setLagoonGlobals($lagoon_bin);

    $this->mockQuit(0);

    $this->expectException(QuitSuccessException::class);

    $this->mockPassthru([
      'cmd' => $this->getSetupSshPath(),
      'output' => 'SSH setup complete',
      'result_code' => 0,
    ]);

    $platform = strtolower(php_uname('s'));
    $arch = str_replace(['x86_64', 'aarch64'], ['amd64', 'arm64'], php_uname('m'));
    $download_url = sprintf(
      'https://github.com/uselagoon/lagoon-cli/releases/download/v0.32.0/lagoon-cli-v0.32.0-%s-%s',
      $platform,
      $arch
    );

    $this->mockRequestGet(
      $download_url,
      [],
      60,
      ['status' => 200, 'ok' => TRUE, 'body' => 'fake binary content']
    );

    $this->mockPassthru([
      'cmd' => sprintf("'%s' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'", $lagoon_bin),
      'output' => 'Config added',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommandWithPath($lagoon_bin, 'list environments --output-json --pretty'),
      'output' => '{"data":[{"name":"main","deploytype":"branch"}]}',
      'result_code' => 0,
    ]);

    $this->mockPassthru([
      'cmd' => $this->getLagoonCommandWithPath($lagoon_bin, "deploy branch --branch 'develop'"),
      'output' => 'Deploy queued',
      'result_code' => 0,
    ]);

    $output = $this->runScript('src/deploy-lagoon');

    $this->assertStringContainsString('Installing Lagoon CLI.', $output);
    $this->assertStringContainsString('Downloading Lagoon CLI from ' . $download_url . '.', $output);
    $this->assertStringContainsString(sprintf('Installing Lagoon CLI to %s.', $lagoon_bin), $output);
    $this->assertStringContainsString('Finished LAGOON deployment.', $output);
  }

  protected function getSetupSshPath(): string {
    return dirname(__DIR__, 2) . '/src/setup-ssh';
  }

  protected function getLagoonCommand(string $subcommand): string {
    $ssh_file = self::$tmp . '/.ssh/id_rsa';
    return sprintf(
      "'lagoon' --force --skip-update-check --ssh-key '%s' --lagoon 'amazeeio' --project 'test-project' %s 2>&1",
      $ssh_file,
      $subcommand
    );
  }

  protected function getLagoonConfigAddCommand(): string {
    return "'lagoon' config add --force --lagoon 'amazeeio' --graphql 'https://api.lagoon.amazeeio.cloud/graphql' --hostname 'ssh.lagoon.amazeeio.cloud' --port '32222'";
  }

  protected function getLagoonCommandWithPath(string $lagoon_bin, string $subcommand): string {
    $ssh_file = self::$tmp . '/.ssh/id_rsa';
    return sprintf(
      "'%s' --force --skip-update-check --ssh-key '%s' --lagoon 'amazeeio' --project 'test-project' %s 2>&1",
      $lagoon_bin,
      $ssh_file,
      $subcommand
    );
  }

  protected function createFakeLagoonBinary(): void {
    // Create a fake lagoon binary in a directory that's in PATH.
    $bin_dir = self::$tmp . '/bin';
    if (!is_dir($bin_dir)) {
      mkdir($bin_dir, 0755, TRUE);
    }

    $lagoon_path = $bin_dir . '/lagoon';
    file_put_contents($lagoon_path, "#!/bin/bash\necho 'fake lagoon'\n");
    chmod($lagoon_path, 0755);

    // Add to PATH so command_exists finds it.
    putenv('PATH=' . $bin_dir . ':' . getenv('PATH'));

    // Set global variables that the script's functions access via 'global'.
    // When scripts are included via require, their variables are local to
    // the including function's scope, but functions using 'global' access
    // the actual global scope ($GLOBALS).
    $this->setLagoonGlobals('lagoon');
  }

  protected function setLagoonGlobals(string $lagoon_bin): void {
    $GLOBALS['lagoon_bin'] = $lagoon_bin;
    $GLOBALS['lagoon_instance'] = 'amazeeio';
    $GLOBALS['lagoon_project'] = 'test-project';
    $GLOBALS['ssh_file'] = self::$tmp . '/.ssh/id_rsa';
    $GLOBALS['deploy_lagoon_fail_when_env_limit_exceeded'] = FALSE;
  }

}
