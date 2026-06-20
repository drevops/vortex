<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for notify-diffy script.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('notify')]
class NotifyDiffyTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSetMultiple([
      'VORTEX_NOTIFY_DIFFY_TOKEN' => 'gho_test123',
      'VORTEX_NOTIFY_DIFFY_REPOSITORY' => 'owner/repo',
      'VORTEX_NOTIFY_DIFFY_BRANCH' => 'main',
      'VORTEX_NOTIFY_DIFFY_ENVIRONMENT_URL' => 'https://example.com',
      'VORTEX_NOTIFY_DIFFY_LABEL' => 'PR-123',
      'VORTEX_NOTIFY_DIFFY_EVENT' => 'post_deployment',
    ]);
  }

  public function testSkippedOnPreDeployment(): void {
    $this->envSet('VORTEX_NOTIFY_DIFFY_EVENT', 'pre_deployment');

    $this->runScriptEarlyPass('src/notify-diffy', 'Skipped Diffy notification for pre_deployment event.');
  }

  public function testSkippedWhenBranchNotInFilter(): void {
    $this->envSet('VORTEX_NOTIFY_DIFFY_BRANCHES', 'develop,master');

    $this->runScriptEarlyPass('src/notify-diffy', "Skipped Diffy notification for branch 'main' (not in branch allowlist).");
  }

  public function testProceedsWhenBranchInFilter(): void {
    $this->envSet('VORTEX_NOTIFY_DIFFY_BRANCHES', 'main,develop');

    $this->mockRequestPost('https://api.github.com/repos/owner/repo/dispatches', NULL, [], 10, ['status' => 204]);

    $output = $this->runScript('src/notify-diffy');

    $this->assertStringContainsString('Finished Diffy notification.', $output);
  }

  public function testSuccessfulDispatch(): void {
    $this->mockRequestPost('https://api.github.com/repos/owner/repo/dispatches', NULL, [], 10, ['status' => 204]);

    $output = $this->runScript('src/notify-diffy');

    $this->assertStringContainsString('Started Diffy notification.', $output);
    $this->assertStringContainsString('Diffy notification summary:', $output);
    $this->assertStringContainsString('owner/repo', $output);
    $this->assertStringContainsString('PR-123', $output);
    $this->assertStringContainsString('production', $output);
    $this->assertStringContainsString('vr_run', $output);
    $this->assertStringContainsString('Finished Diffy notification.', $output);
  }

  public function testDispatchFailure(): void {
    $this->mockRequestPost('https://api.github.com/repos/owner/repo/dispatches', NULL, [], 10, ['status' => 401]);

    $this->runScriptError('src/notify-diffy', 'GitHub repository_dispatch failed with HTTP 401.');
  }

  public function testRepositorySanitization(): void {
    $this->envSet('VORTEX_NOTIFY_DIFFY_REPOSITORY', 'https://github.com/owner/repo.git');

    $this->mockRequestPost('https://api.github.com/repos/owner/repo/dispatches', NULL, [], 10, ['status' => 204]);

    $output = $this->runScript('src/notify-diffy');

    $this->assertStringContainsString('owner/repo', $output);
    $this->assertStringNotContainsString('github.com', $output);
    $this->assertStringContainsString('Finished Diffy notification.', $output);
  }

  public function testMissingToken(): void {
    $this->envUnset('VORTEX_NOTIFY_DIFFY_TOKEN');

    $this->runScriptError('src/notify-diffy', 'Missing required value for VORTEX_NOTIFY_DIFFY_TOKEN.');
  }

  public function testMissingLabel(): void {
    $this->envUnset('VORTEX_NOTIFY_DIFFY_LABEL');

    $this->runScriptError('src/notify-diffy', 'Missing required value for VORTEX_NOTIFY_DIFFY_LABEL.');
  }

  public function testFallbackVariables(): void {
    $this->envUnsetMultiple([
      'VORTEX_NOTIFY_DIFFY_TOKEN',
      'VORTEX_NOTIFY_DIFFY_REPOSITORY',
      'VORTEX_NOTIFY_DIFFY_BRANCH',
      'VORTEX_NOTIFY_DIFFY_ENVIRONMENT_URL',
      'VORTEX_NOTIFY_DIFFY_LABEL',
      'VORTEX_NOTIFY_DIFFY_EVENT',
    ]);
    $this->envSetMultiple([
      'VORTEX_NOTIFY_GITHUB_TOKEN' => 'gho_fallback',
      'VORTEX_NOTIFY_GITHUB_REPOSITORY' => 'owner/fallback',
      'VORTEX_NOTIFY_BRANCH' => 'main',
      'VORTEX_NOTIFY_ENVIRONMENT_URL' => 'https://fallback.example.com',
      'VORTEX_NOTIFY_LABEL' => 'PR-456',
      'VORTEX_NOTIFY_EVENT' => 'post_deployment',
    ]);

    $this->mockRequestPost('https://api.github.com/repos/owner/fallback/dispatches', NULL, [], 10, ['status' => 204]);

    $output = $this->runScript('src/notify-diffy');

    $this->assertStringContainsString('owner/fallback', $output);
    $this->assertStringContainsString('PR-456', $output);
    $this->assertStringContainsString('Finished Diffy notification.', $output);
  }

}
