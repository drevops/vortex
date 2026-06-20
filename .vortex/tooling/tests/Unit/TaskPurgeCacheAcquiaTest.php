<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[Group('scripts')]
#[RunTestsInSeparateProcesses]
class TaskPurgeCacheAcquiaTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY', 'test-key');
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET', 'test-secret');
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME', 'myapp');
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV', 'dev');
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_DOMAINS_FILE', self::$tmp . '/domains.txt');
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_RETRIES', '2');
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_STATUS_INTERVAL', '1');
  }

  public function testMissingKey(): void {
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY', '');
    $this->envUnset('VORTEX_ACQUIA_KEY');

    $this->runScriptError('src/task-purge-cache-acquia', 'Missing required value for VORTEX_TASK_PURGE_CACHE_ACQUIA_KEY');
  }

  public function testMissingSecret(): void {
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET', '');
    $this->envUnset('VORTEX_ACQUIA_SECRET');

    $this->runScriptError('src/task-purge-cache-acquia', 'Missing required value for VORTEX_TASK_PURGE_CACHE_ACQUIA_SECRET');
  }

  public function testMissingAppName(): void {
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME', '');
    $this->envUnset('VORTEX_ACQUIA_APP_NAME');

    $this->runScriptError('src/task-purge-cache-acquia', 'Missing required value for VORTEX_TASK_PURGE_CACHE_ACQUIA_APP_NAME');
  }

  public function testMissingEnv(): void {
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV', '');

    $this->runScriptError('src/task-purge-cache-acquia', 'Missing required value for VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV');
  }

  public function testDomainsFileNotFound(): void {
    $this->mockSleep();

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-dev']]]])],
      ],
    ]);

    $this->runScriptError('src/task-purge-cache-acquia', 'Domains file');
  }

  public function testSuccessDevEnv(): void {
    $this->mockSleep();

    file_put_contents(self::$tmp . '/domains.txt', '$target_env.example.com' . "\n");

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
      // Env ID.
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-dev']]]])],
      ],
      // Purge domain.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-dev/domains/actions/clear-varnish',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/purge-1']]])],
      ],
      // Polling: status completed (reuses existing token).
      [
        'url' => 'https://cloud.acquia.com/api/notifications/purge-1',
        'response' => ['body' => json_encode(['status' => 'completed'])],
      ],
    ]);

    $output = $this->runScript('src/task-purge-cache-acquia');

    $this->assertStringContainsString('Started cache purging in Acquia.', $output);
    $this->assertStringContainsString('Purged cache for dev environment domain dev.example.com.', $output);
    $this->assertStringContainsString('Finished cache purging in Acquia.', $output);
  }

  public function testSuccessProdEnv(): void {
    $this->mockSleep();
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV', 'prod');

    file_put_contents(self::$tmp . '/domains.txt', '$target_env.example.com' . "\n");

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dprod',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-prod']]]])],
      ],
      // Purge domain - prod strips $target_env. prefix.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-prod/domains/actions/clear-varnish',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/purge-1']]])],
      ],
      // Polling: status completed (reuses existing token).
      [
        'url' => 'https://cloud.acquia.com/api/notifications/purge-1',
        'response' => ['body' => json_encode(['status' => 'completed'])],
      ],
    ]);

    $output = $this->runScript('src/task-purge-cache-acquia');

    // Prod strips $target_env prefix from domain names.
    $this->assertStringContainsString('Purged cache for prod environment domain example.com.', $output);
    $this->assertStringContainsString('Finished cache purging in Acquia.', $output);
  }

  public function testTestEnvRemapsToStage(): void {
    $this->mockSleep();
    $this->envSet('VORTEX_TASK_PURGE_CACHE_ACQUIA_ENV', 'test');

    file_put_contents(self::$tmp . '/domains.txt', '$target_env_remap.example.com' . "\n");

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Dtest',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-test']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-test/domains/actions/clear-varnish',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/purge-1']]])],
      ],
      // Polling: status completed (reuses existing token).
      [
        'url' => 'https://cloud.acquia.com/api/notifications/purge-1',
        'response' => ['body' => json_encode(['status' => 'completed'])],
      ],
    ]);

    $output = $this->runScript('src/task-purge-cache-acquia');

    // Test env remaps $target_env_remap to 'stage'.
    $this->assertStringContainsString('Purged cache for test environment domain stage.example.com.', $output);
    $this->assertStringContainsString('Finished cache purging in Acquia.', $output);
  }

  public function testDomainPurgeWarning(): void {
    $this->mockSleep();

    file_put_contents(self::$tmp . '/domains.txt', '$target_env.example.com' . "\n");

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-dev']]]])],
      ],
      // Purge returns no notification URL (domain doesn't exist).
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-dev/domains/actions/clear-varnish',
        'response' => ['body' => json_encode([])],
      ],
    ]);

    $output = $this->runScript('src/task-purge-cache-acquia');

    $this->assertStringContainsString('Unable to purge cache for dev environment domain dev.example.com as it does not exist.', $output);
    $this->assertStringContainsString('Finished cache purging in Acquia.', $output);
  }

  public function testNoDomains(): void {
    $this->mockSleep();

    // Empty domains file (only comments).
    file_put_contents(self::$tmp . '/domains.txt', "# This is a comment\n\n");

    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['body' => json_encode(['access_token' => 'test-token'])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications?filter=name%3Dmyapp',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['uuid' => 'app-uuid-123']]]])],
      ],
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-dev']]]])],
      ],
    ]);

    $output = $this->runScript('src/task-purge-cache-acquia');

    $this->assertStringContainsString('Unable to find domains to purge cache for dev environment.', $output);
    $this->assertStringContainsString('Finished cache purging in Acquia.', $output);
  }

  public function testMultipleDomains(): void {
    $this->mockSleep();

    file_put_contents(self::$tmp . '/domains.txt', "# Comment\n\$target_env.example.com\n\$target_env.example2.com\n");

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
      // Env ID.
      [
        'url' => 'https://cloud.acquia.com/api/applications/app-uuid-123/environments?filter=name%3Ddev',
        'response' => ['body' => json_encode(['_embedded' => ['items' => [['id' => 'env-id-dev']]]])],
      ],
      // Purge domain 1.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-dev/domains/actions/clear-varnish',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/purge-1']]])],
      ],
      // Polling domain 1: status completed (reuses existing token).
      [
        'url' => 'https://cloud.acquia.com/api/notifications/purge-1',
        'response' => ['body' => json_encode(['status' => 'completed'])],
      ],
      // Purge domain 2.
      [
        'url' => 'https://cloud.acquia.com/api/environments/env-id-dev/domains/actions/clear-varnish',
        'response' => ['body' => json_encode(['_links' => ['notification' => ['href' => 'https://cloud.acquia.com/api/notifications/purge-2']]])],
      ],
      // Polling domain 2: status completed (reuses existing token).
      [
        'url' => 'https://cloud.acquia.com/api/notifications/purge-2',
        'response' => ['body' => json_encode(['status' => 'completed'])],
      ],
    ]);

    $output = $this->runScript('src/task-purge-cache-acquia');

    $this->assertStringContainsString('Purged cache for dev environment domain dev.example.com.', $output);
    $this->assertStringContainsString('Purged cache for dev environment domain dev.example2.com.', $output);
    $this->assertStringContainsString('Finished cache purging in Acquia.', $output);
  }

  public function testTokenError(): void {
    $this->mockRequestMultiple([
      [
        'url' => 'https://accounts.acquia.com/api/auth/oauth/token',
        'response' => ['ok' => FALSE, 'status' => 401, 'body' => ''],
      ],
    ]);

    $this->runScriptError('src/task-purge-cache-acquia', 'Unable to retrieve a token');
  }

}
