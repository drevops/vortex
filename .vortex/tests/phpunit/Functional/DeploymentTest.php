<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use AlexSkrypnyk\File\File;
use DrevOps\Vortex\Tests\Traits\Subtests\SubtestAhoyTrait;
use DrevOps\Vortex\Tests\Traits\Subtests\SubtestDeploymentTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests deployment workflows.
 */
class DeploymentTest extends FunctionalTestCase {

  use SubtestAhoyTrait;
  use SubtestDeploymentTrait;

  protected function setUp(): void {
    parent::setUp();

    static::$sutInstallerEnv = [];

    $this->dockerCleanup();
  }

  #[Group('p2')]
  public function testDeployment(): void {
    $this->logStepStart();

    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_IS_DEMO' => '1',
    ];

    $this->logSubstep('Prepare SUT without full build (structure only)');
    $this->prepareSut();
    $this->createInstalledDependenciesStub();

    $this->logSubstep('Run webhook deployment');
    $this->cmd('ahoy deploy', [
      '* Started WEBHOOK deployment.',
      '* Webhook call completed.',
      '! [FAIL] Unable to complete webhook deployment.',
      '* Finished WEBHOOK deployment.',
    ], txt: 'Webhook deployment should complete successfully', env: [
      'VORTEX_DEPLOY_TYPES' => 'webhook',
      'VORTEX_DEPLOY_WEBHOOK_URL' => 'https://www.example.com',
      'VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS' => '200',
    ]);

    $this->logStepFinish();
  }

  #[Group('p3')]
  public function testDeploymentSkipFlags(): void {
    $this->logStepStart();

    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_IS_DEMO' => '1',
    ];

    $this->logSubstep('Prepare SUT without full build (structure only)');
    $this->prepareSut();
    $this->createInstalledDependenciesStub();

    $this->logSubstep('Subtest 1: Run deployment without skip flag set');
    $this->cmd('ahoy deploy', [
      '* Started WEBHOOK deployment.',
      '* Finished WEBHOOK deployment.',
      '! Skipping deployment webhook.',
    ], txt: 'Deployment should proceed without skip flag', env: [
      'VORTEX_DEPLOY_TYPES' => 'webhook',
      'VORTEX_DEPLOY_WEBHOOK_URL' => 'https://www.example.com',
      'VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS' => '200',
    ]);

    $this->logSubstep('Subtest 2: Run deployment with skip flag but no per-branch skip');
    $this->cmd('ahoy deploy', [
      '* Found flag to skip a deployment.',
      '* Started WEBHOOK deployment.',
      '* Finished WEBHOOK deployment.',
      '! Skipping deployment webhook.',
    ], txt: 'Deployment should proceed with ALLOW_SKIP but no specific skip', env: [
      'VORTEX_DEPLOY_TYPES' => 'webhook',
      'VORTEX_DEPLOY_WEBHOOK_URL' => 'https://www.example.com',
      'VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS' => '200',
      'VORTEX_DEPLOY_ALLOW_SKIP' => '1',
    ]);

    $this->logSubstep('Subtest 3: Run deployment with per-branch skip flag');
    $this->cmd('ahoy deploy', [
      '* Found flag to skip a deployment.',
      '* Found skip variable VORTEX_DEPLOY_SKIP_BRANCH_FEATURE_TEST',
      '* Skipping deployment webhook.',
      '! Started WEBHOOK deployment.',
      '! Finished WEBHOOK deployment.',
    ], txt: 'Deployment should be skipped for feature/test branch', env: [
      'VORTEX_DEPLOY_TYPES' => 'webhook',
      'VORTEX_DEPLOY_WEBHOOK_URL' => 'https://www.example.com',
      'VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS' => '200',
      'VORTEX_DEPLOY_ALLOW_SKIP' => '1',
      'VORTEX_DEPLOY_BRANCH' => 'feature/test',
      'VORTEX_DEPLOY_SKIP_BRANCH_FEATURE_TEST' => '1',
    ]);

    $this->logSubstep('Subtest 4: Run deployment with per-PR skip flag');
    $this->cmd('ahoy deploy', [
      '* Found flag to skip a deployment.',
      '* Found skip variable VORTEX_DEPLOY_SKIP_PR_123',
      '* Skipping deployment webhook.',
      '! Started WEBHOOK deployment.',
      '! Finished WEBHOOK deployment.',
    ], txt: 'Deployment should be skipped for PR 123', env: [
      'VORTEX_DEPLOY_TYPES' => 'webhook',
      'VORTEX_DEPLOY_WEBHOOK_URL' => 'https://www.example.com',
      'VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS' => '200',
      'VORTEX_DEPLOY_ALLOW_SKIP' => '1',
      'VORTEX_DEPLOY_PR' => '123',
      'VORTEX_DEPLOY_SKIP_PR_123' => '1',
    ]);

    $this->logSubstep('Subtest 5: Run deployment without skip flag but with per-PR');
    $this->cmd('ahoy deploy', [
      '* Started WEBHOOK deployment.',
      '* Finished WEBHOOK deployment.',
      '! Found flag to skip a deployment.',
      '! Skipping deployment webhook.',
    ], txt: 'Deployment should proceed when ALLOW_SKIP is not set', env: [
      'VORTEX_DEPLOY_TYPES' => 'webhook',
      'VORTEX_DEPLOY_WEBHOOK_URL' => 'https://www.example.com',
      'VORTEX_DEPLOY_WEBHOOK_RESPONSE_STATUS' => '200',
      'VORTEX_DEPLOY_PR' => '123',
      'VORTEX_DEPLOY_SKIP_PR_123' => '1',
    ]);

    $this->logStepFinish();
  }

  #[Group('p2')]
  public function testDeploymentArtifact(): void {
    $this->logStepStart();

    static::$sutInstallerEnv = [
      'VORTEX_INSTALLER_IS_DEMO' => '1',
      // Add trailing comma to simulate list input.
      'VORTEX_INSTALLER_PROMPT_DEPLOY_TYPES' => 'artifact,',
    ];

    $this->logSubstep('Prepare SUT with full build');
    $this->prepareSut();
    $this->adjustAhoyForUnmountedVolumes();

    $this->logSubstep('Build site');
    $this->subtestAhoyBuild();
    $this->syncToHost();

    $this->logSubstep('Prepare deployment directories');
    $src_dir = static::$workspace . '/deployment_src';
    $remote_dir = static::$workspace . '/deployment_remote';

    $this->prepareDeploymentSource($src_dir);
    $this->prepareRemoteRepository($remote_dir);

    $this->logSubstep('Copy built codebase to deployment source');
    // Copy everything including .git directory to match BATS test behavior.
    // The deployment artifact script expects the source to be a git repository
    // with .gitignore.artifact already present.
    File::copy(static::$sut . '/.', $src_dir . '/');

    $this->logSubstep('Create excluded directory (should be ignored in artifact)');
    File::mkdir($src_dir . '/web/themes/custom/star_wars/node_modules');
    File::dump($src_dir . '/web/themes/custom/star_wars/node_modules/test.txt', '');

    $this->logSubstep('Run artifact deployment');
    $this->cmd('ahoy deploy', [
      '* Started ARTIFACT deployment.',
      '* Installing artifact builder.',
      '* Copying git repo files meta file to the deploy code repo.',
      '* Copying deployment .gitignore as it may not exist in deploy code source files.',
      '* Running artifact builder.',
      '* Finished ARTIFACT deployment.',
    ], txt: 'Artifact deployment should complete successfully', env: [
      'VORTEX_DEPLOY_TYPES' => 'artifact',
      'VORTEX_DEPLOY_ARTIFACT_GIT_REMOTE' => $remote_dir . '/.git',
      'VORTEX_DEPLOY_ARTIFACT_ROOT' => static::$sut,
      'VORTEX_DEPLOY_ARTIFACT_SRC' => $src_dir,
      'VORTEX_DEPLOY_ARTIFACT_GIT_USER_EMAIL' => 'testuser@example.com',
    ]);

    $this->logSubstep('Assert deployment files in remote repository');
    $this->assertDeploymentFilesPresent($remote_dir);

    $this->logStepFinish();
  }

}
