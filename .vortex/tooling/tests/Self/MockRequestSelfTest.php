<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Self;

use DrevOps\VortexTooling\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Self-tests for mocking of HTTP request functions.
 *
 * We test mockRequest() to ensure it returns the correct responses
 * and validates URL/method expectations.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversClass(UnitTestCase::class)]
class MockRequestSelfTest extends UnitTestCase {

  public function testMockRequestGetSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/api',
      [],
      10,
      ['status' => 200, 'body' => 'success response']
    );

    // Load helpers.php AFTER setting up mocks.
    require_once __DIR__ . '/../../src/helpers.php';

    $result = \DrevOps\VortexTooling\request_get('https://example.com/api');

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
    $this->assertEquals('success response', $result['body']);
    $this->assertNull($result['error']);
  }

  public function testMockRequestGetCustomSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/api',
      [],
      10,
      [
        'ok' => TRUE,
        'status' => 201,
        'body' => 'custom body content',
        'error' => NULL,
        'info' => ['http_code' => 201, 'total_time' => 1.5],
      ]
    );

    $result = \DrevOps\VortexTooling\request_get('https://example.com/api');

    $this->assertTrue($result['ok']);
    $this->assertEquals(201, $result['status']);
    $this->assertEquals('custom body content', $result['body']);
    $this->assertNull($result['error']);
    $this->assertEquals(201, $result['info']['http_code']);
    $this->assertEquals(1.5, $result['info']['total_time']);
  }

  public function testMockRequestGetDefaultsSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/api',
      [],
      10,
      ['status' => 200]
    );

    $result = \DrevOps\VortexTooling\request_get('https://example.com/api');

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
    $this->assertEquals('', $result['body']);
    $this->assertNull($result['error']);
  }

  public function testMockRequestGetFailure404(): void {
    $this->mockRequestGet(
      'https://example.com/not-found',
      [],
      10,
      [
        'ok' => FALSE,
        'status' => 404,
        'body' => 'Not Found',
      ]
    );

    $result = \DrevOps\VortexTooling\request_get('https://example.com/not-found');

    $this->assertFalse($result['ok']);
    $this->assertEquals(404, $result['status']);
    $this->assertEquals('Not Found', $result['body']);
  }

  public function testMockRequestGetFailure500(): void {
    $this->mockRequestGet(
      'https://example.com/error',
      [],
      10,
      [
        'ok' => FALSE,
        'status' => 500,
        'body' => 'Internal Server Error',
      ]
    );

    $result = \DrevOps\VortexTooling\request_get('https://example.com/error');

    $this->assertFalse($result['ok']);
    $this->assertEquals(500, $result['status']);
    $this->assertEquals('Internal Server Error', $result['body']);
  }

  public function testMockRequestGetNetworkError(): void {
    $this->mockRequestGet(
      'https://example.com/timeout',
      [],
      10,
      [
        'ok' => FALSE,
        'status' => 0,
        'body' => FALSE,
        'error' => 'Could not resolve host',
      ]
    );

    $result = \DrevOps\VortexTooling\request_get('https://example.com/timeout');

    $this->assertFalse($result['ok']);
    $this->assertEquals(0, $result['status']);
    $this->assertFalse($result['body']);
    $this->assertEquals('Could not resolve host', $result['error']);
  }

  public function testMockRequestPostSuccess(): void {
    $this->mockRequestPost(
      'https://example.com/api',
      '{"key":"value"}',
      [],
      10,
      ['status' => 201, 'body' => 'created']
    );

    $result = \DrevOps\VortexTooling\request_post('https://example.com/api', '{"key":"value"}');

    $this->assertTrue($result['ok']);
    $this->assertEquals(201, $result['status']);
    $this->assertEquals('created', $result['body']);
  }

  public function testMockRequestPostJsonSuccess(): void {
    $this->mockRequestPost(
      'https://example.com/api/json',
      json_encode(['data' => 'value']),
      ['Content-Type: application/json'],
      10,
      ['status' => 200, 'body' => '{"success":true}']
    );

    $result = \DrevOps\VortexTooling\request_post(
      'https://example.com/api/json',
      json_encode(['data' => 'value']),
      ['Content-Type: application/json']
    );

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
    $this->assertEquals('{"success":true}', $result['body']);
  }

  public function testMockRequestCustomMethodSuccess(): void {
    $this->mockRequest(
      'https://example.com/resource',
      ['method' => 'PUT'],
      ['status' => 200, 'body' => 'updated']
    );

    $result = \DrevOps\VortexTooling\request('https://example.com/resource', ['method' => 'PUT']);

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
    $this->assertEquals('updated', $result['body']);
  }

  public function testMockRequestMultipleSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/first',
      [],
      10,
      ['status' => 200, 'body' => 'first response']
    );

    $this->mockRequestPost(
      'https://example.com/second',
      'data',
      [],
      10,
      ['status' => 201, 'body' => 'second response']
    );

    $this->mockRequest(
      'https://example.com/third',
      ['method' => 'PUT'],
      ['status' => 200, 'body' => 'third response']
    );

    $result1 = \DrevOps\VortexTooling\request_get('https://example.com/first');
    $this->assertEquals(200, $result1['status']);
    $this->assertEquals('first response', $result1['body']);

    $result2 = \DrevOps\VortexTooling\request_post('https://example.com/second', 'data');
    $this->assertEquals(201, $result2['status']);
    $this->assertEquals('second response', $result2['body']);

    $result3 = \DrevOps\VortexTooling\request('https://example.com/third', ['method' => 'PUT']);
    $this->assertEquals(200, $result3['status']);
    $this->assertEquals('third response', $result3['body']);
  }

  public function testMockRequestMultipleMoreCallsFailure(): void {
    $this->mockRequestGet('https://example.com/first', [], 10, ['status' => 200, 'body' => 'first']);
    $this->mockRequestGet('https://example.com/second', [], 10, ['status' => 200, 'body' => 'second']);

    \DrevOps\VortexTooling\request_get('https://example.com/first');
    \DrevOps\VortexTooling\request_get('https://example.com/second');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('curl_init() called more times than mocked responses. Expected 2 request(s), but attempting request #3');

    \DrevOps\VortexTooling\request_get('https://example.com/third');
  }

  public function testMockRequestMultipleLessCallsFailure(): void {
    $this->mockRequestGet('https://example.com/first', [], 10, ['status' => 200, 'body' => 'first']);
    $this->mockRequestGet('https://example.com/second', [], 10, ['status' => 200, 'body' => 'second']);
    $this->mockRequestGet('https://example.com/third', [], 10, ['status' => 200, 'body' => 'third']);

    \DrevOps\VortexTooling\request_get('https://example.com/first');
    \DrevOps\VortexTooling\request_get('https://example.com/second');

    $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked request responses were consumed. Expected 3 call(s), but only 2 call(s) were made.');

    // Manually trigger the check that normally happens in tearDown().
    $this->mockRequestAssertAllMocksConsumed();
  }

  public function testMockRequestFailureAssertUnexpectedUrl(): void {
    $this->mockRequestGet(
      'https://example.com/expected',
      [],
      10,
      ['status' => 200]
    );

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('request made to unexpected URL. Expected "https://example.com/expected", got "https://example.com/actual".');

    \DrevOps\VortexTooling\request_get('https://example.com/actual');
  }

  public function testMockRequestGetScriptPassingSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/api',
      [],
      10,
      [
        'status' => 200,
        'body' => 'success response',
      ]
    );

    $output = $this->runScript('tests/Fixtures/test-request-get-passing');

    $this->assertStringContainsString('Script will call request_get', $output);
    $this->assertStringContainsString('Response status: 200', $output);
    $this->assertStringContainsString('Response ok: true', $output);
    $this->assertStringContainsString('Response body: success response', $output);
  }

  public function testMockRequestGetScriptFailingSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/not-found',
      [],
      10,
      [
        'ok' => FALSE,
        'status' => 404,
        'body' => 'Not Found',
      ]
    );

    $output = $this->runScript('tests/Fixtures/test-request-get-failing');

    $this->assertStringContainsString('Script will call request_get expecting failure', $output);
    $this->assertStringContainsString('Response status: 404', $output);
    $this->assertStringContainsString('Response ok: false', $output);
    $this->assertStringContainsString('Request failed as expected', $output);
  }

  public function testMockRequestGetScriptCustomSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/api',
      [],
      10,
      [
        'status' => 201,
        'body' => 'custom response',
      ]
    );

    $output = $this->runScript('tests/Fixtures/test-request-get-passing');

    $this->assertStringContainsString('Response status: 201', $output);
    $this->assertStringContainsString('Response body: custom response', $output);
  }

  public function testMockRequestGetScriptDefaultsSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/api',
      [],
      10,
      ['status' => 200]
    );

    $output = $this->runScript('tests/Fixtures/test-request-get-passing');

    $this->assertStringContainsString('Response status: 200', $output);
    $this->assertStringContainsString('Response ok: true', $output);
  }

  public function testMockRequestGetScriptNetworkErrorSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/not-found',
      [],
      10,
      [
        'ok' => FALSE,
        'status' => 0,
        'body' => FALSE,
        'error' => 'Could not resolve host',
      ]
    );

    $output = $this->runScript('tests/Fixtures/test-request-get-failing');

    $this->assertStringContainsString('Response status: 0', $output);
    $this->assertStringContainsString('Response ok: false', $output);
  }

  public function testMockRequestPostScriptPassingSuccess(): void {
    $this->mockRequestPost(
      'https://example.com/api',
      NULL,
      [],
      10,
      [
        'status' => 201,
        'body' => 'created',
      ]
    );

    $output = $this->runScript('tests/Fixtures/test-request-post-passing');

    $this->assertStringContainsString('Script will call request_post', $output);
    $this->assertStringContainsString('Response status: 201', $output);
    $this->assertStringContainsString('Response ok: true', $output);
    $this->assertStringContainsString('POST succeeded', $output);
  }

  public function testMockRequestPostScriptFailingSuccess(): void {
    $this->mockRequestPost(
      'https://example.com/error',
      NULL,
      [],
      10,
      [
        'ok' => FALSE,
        'status' => 500,
        'body' => 'Internal Server Error',
      ]
    );

    $output = $this->runScript('tests/Fixtures/test-request-post-failing');

    $this->assertStringContainsString('Script will call request_post expecting failure', $output);
    $this->assertStringContainsString('Response status: 500', $output);
    $this->assertStringContainsString('Response ok: false', $output);
    $this->assertStringContainsString('POST failed as expected', $output);
  }

  public function testMockRequestMultipleScriptSuccess(): void {
    $this->mockRequestGet(
      'https://example.com/first',
      [],
      10,
      ['status' => 200, 'body' => 'first']
    );

    $this->mockRequestPost(
      'https://example.com/second',
      NULL,
      [],
      10,
      ['status' => 201, 'body' => 'second']
    );

    $this->mockRequest(
      'https://example.com/third',
      ['method' => 'PUT'],
      ['status' => 200, 'body' => 'third']
    );

    $output = $this->runScript('tests/Fixtures/test-request-multiple');

    $this->assertStringContainsString('Script will call request functions multiple times', $output);
    $this->assertStringContainsString('First call status: 200', $output);
    $this->assertStringContainsString('Second call status: 201', $output);
    $this->assertStringContainsString('Third call status: 200', $output);
    $this->assertStringContainsString('Script completed', $output);
  }

  public function testMockRequestMultipleScriptMoreCallsFailure(): void {
    $this->mockRequestGet(
      'https://example.com/first',
      [],
      10,
      ['status' => 200, 'body' => 'first']
    );

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('curl_init() called more times than mocked responses. Expected 1 request(s), but attempting request #2');

    $this->runScript('tests/Fixtures/test-request-multiple');
  }

  public function testMockRequestMultipleScriptLessCallsFailure(): void {
    $this->mockRequestGet(
      'https://example.com/first',
      [],
      10,
      ['status' => 200, 'body' => 'first']
    );

    $this->mockRequestPost(
      'https://example.com/second',
      NULL,
      [],
      10,
      ['status' => 201, 'body' => 'second']
    );

    $this->mockRequest(
      'https://example.com/third',
      ['method' => 'PUT'],
      ['status' => 200, 'body' => 'third']
    );

    $this->mockRequestGet(
      'https://example.com/fourth',
      [],
      10,
      ['status' => 200, 'body' => 'fourth']
    );

    $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
    $this->expectExceptionMessage('Not all mocked request responses were consumed. Expected 4 call(s), but only 3 call(s) were made.');

    $this->runScript('tests/Fixtures/test-request-multiple');

    // Manually trigger the check that normally happens in tearDown().
    $this->mockRequestAssertAllMocksConsumed();
  }

  public function testMockRequestGetScriptFailureAssertUnexpectedUrl(): void {
    $this->mockRequestGet(
      'https://wrong.com/api',
      [],
      10,
      ['status' => 200]
    );

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('request made to unexpected URL. Expected "https://wrong.com/api", got "https://example.com/api".');

    $this->runScript('tests/Fixtures/test-request-get-passing');
  }

  public function testMockRequestFailureMissingUrlKey(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Mocked request response must include "url" key to specify expected URL.');

    // Call mockRequestMultiple directly with malformed
    // response (missing 'url' key).
    // @phpstan-ignore-next-line argument.type
    $this->mockRequestMultiple([
      [
        'method' => 'GET',
        'response' => ['status' => 200, 'body' => 'test'],
      ],
    ]);

    // Trigger curl_exec which validates the response structure.
    \DrevOps\VortexTooling\request_get('https://example.com/api');
  }

  public function testMockRequestFailureMethodMismatch(): void {
    $this->mockRequest(
      'https://example.com/api',
      ['method' => 'POST'],
      ['status' => 200, 'body' => 'test']
    );

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('request made with unexpected method. Expected "POST", got "GET".');

    // Make GET request when POST was expected.
    \DrevOps\VortexTooling\request_get('https://example.com/api');
  }

}
