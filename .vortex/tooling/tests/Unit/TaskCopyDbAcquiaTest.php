<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class TaskCopyDbAcquiaTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_KEY', 'test-key');
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_SECRET', 'test-secret');
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_APP_NAME', 'myapp');
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_SRC', 'prod');
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_DST', 'dev');
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_NAME', 'mydb');
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_STATUS_RETRIES', '3');
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_STATUS_INTERVAL', '1');
  }

  public function testMissingKey(): void {
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_KEY', '');
    $this->envUnset('VORTEX_ACQUIA_KEY');

    $this->runScriptError('src/task-copy-db-acquia', 'Missing required value for VORTEX_TASK_COPY_DB_ACQUIA_KEY');
  }

  public function testMissingSecret(): void {
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_SECRET', '');
    $this->envUnset('VORTEX_ACQUIA_SECRET');

    $this->runScriptError('src/task-copy-db-acquia', 'Missing required value for VORTEX_TASK_COPY_DB_ACQUIA_SECRET');
  }

  public function testMissingAppName(): void {
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_APP_NAME', '');
    $this->envUnset('VORTEX_ACQUIA_APP_NAME');

    $this->runScriptError('src/task-copy-db-acquia', 'Missing required value for VORTEX_TASK_COPY_DB_ACQUIA_APP_NAME');
  }

  public function testMissingSrc(): void {
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_SRC', '');

    $this->runScriptError('src/task-copy-db-acquia', 'Missing required value for VORTEX_TASK_COPY_DB_ACQUIA_SRC');
  }

  public function testMissingDst(): void {
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_DST', '');

    $this->runScriptError('src/task-copy-db-acquia', 'Missing required value for VORTEX_TASK_COPY_DB_ACQUIA_DST');
  }

  public function testMissingDbName(): void {
    $this->envSet('VORTEX_TASK_COPY_DB_ACQUIA_NAME', '');

    $this->runScriptError('src/task-copy-db-acquia', 'Missing required value for VORTEX_TASK_COPY_DB_ACQUIA_NAME');
  }

  public function testSuccess(): void {
    $this->mockSleep();

    $this->mockRequestMultiple([
      // Token.
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      // App UUID.
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      // Source env ID.
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-src-id']]]])],
      ],
      // Destination env ID.
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-dst-id']]]])],
      ],
      // Copy DB request.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-dst-id/databases',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/123']]])],
      ],
      // Polling: token refresh.
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token-2'])],
      ],
      // Polling: notification status (completed).
      [
        'url' => 'https://cloud.acquia.com/api/notifications/123',
        'response' => ['body' => json_encode(['status' => 'completed'])],
      ],
    ]);

    $output = $this->runScript('src/task-copy-db-acquia');

    $this->assertStringContainsString('Started database copying between environments in Acquia.', $output);
    $this->assertStringContainsString('Copied DB from prod to dev environment.', $output);
    $this->assertStringContainsString('Finished database copying between environments in Acquia.', $output);
  }

  public function testPollingTimeout(): void {
    $this->mockSleep();

    $requests = [
      // Token.
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      // App UUID.
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      // Source env ID.
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-src-id']]]])],
      ],
      // Destination env ID.
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-dst-id']]]])],
      ],
      // Copy DB request.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-dst-id/databases',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/123']]])],
      ],
    ];

    // 3 polling iterations, each with token refresh + notification check.
    for ($i = 0; $i < 3; $i++) {
      $requests[] = [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'poll-token-' . $i])],
      ];
      $requests[] = [
        'url' => 'https://cloud.acquia.com/api/notifications/123',
        'response' => ['body' => json_encode(['status' => 'in_progress'])],
      ];
    }

    $this->mockRequestMultiple($requests);

    $this->runScriptError('src/task-copy-db-acquia', 'Unable to copy DB from prod to dev environment');
  }

  public function testTokenError(): void {
    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['ok' => FALSE, 'status' => 401, 'body' => ''],
      ],
    ]);

    $this->runScriptError('src/task-copy-db-acquia', 'Unable to retrieve a token');
  }

}
