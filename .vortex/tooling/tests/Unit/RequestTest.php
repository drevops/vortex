<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;

/**
 * Tests for HTTP request functions.
 *
 * These tests make real network calls to example.com to verify
 * HTTP request functionality works correctly.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[CoversFunction('DrevOps\VortexTooling\request_get')]
#[CoversFunction('DrevOps\VortexTooling\request_post')]
#[CoversFunction('DrevOps\VortexTooling\request')]
class RequestTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Load helpers to make functions available.
    require_once __DIR__ . '/../../src/helpers.php';
  }

  /**
   * Test request_get() with real network call to example.com.
   */
  public function testRequestGetReal(): void {
    $result = \DrevOps\VortexTooling\request_get('http://example.com/');

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
    $this->assertIsString($result['body']);
    $this->assertStringContainsString('Example Domain', $result['body']);
    $this->assertNull($result['error']);
  }

  /**
   * Test request_get() with custom headers to example.com.
   */
  public function testRequestGetWithHeaders(): void {
    $result = \DrevOps\VortexTooling\request_get(
      'http://example.com/',
      ['User-Agent: TestBot/1.0'],
      10
    );

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
  }

  /**
   * Test request_post() with real network call to example.com.
   *
   * Note: example.com may return various error codes (403, 411, etc.)
   * depending on headers and rate limiting. We just verify the function
   * works and returns a valid response structure.
   */
  public function testRequestPostReal(): void {
    $result = \DrevOps\VortexTooling\request_post(
      'http://example.com/',
      'test data',
      ['Content-Type: text/plain'],
      10
    );

    // Verify response structure and status code.
    $this->assertArrayHasKey('ok', $result);
    $this->assertArrayHasKey('status', $result);
    $this->assertArrayHasKey('body', $result);
    $this->assertArrayHasKey('error', $result);
    // example.com typically returns 4xx for POST (403, 411, etc.).
    $this->assertGreaterThanOrEqual(400, $result['status']);
    $this->assertLessThan(500, $result['status']);
  }

  /**
   * Test request() with custom method.
   *
   * Note: Testing with HEAD method to example.com.
   */
  public function testRequestCustomMethod(): void {
    $result = \DrevOps\VortexTooling\request('http://example.com/', [
      'method' => 'HEAD',
      'timeout' => 10,
    ]);

    // Verify response structure and status code.
    $this->assertArrayHasKey('ok', $result);
    $this->assertArrayHasKey('status', $result);
    $this->assertArrayHasKey('body', $result);
    $this->assertArrayHasKey('error', $result);
    // HEAD should return a response (2xx or 4xx).
    $this->assertGreaterThanOrEqual(200, $result['status']);
    $this->assertLessThan(500, $result['status']);
  }

  /**
   * Test request() handles 404 errors.
   */
  public function testRequestNotFound(): void {
    $result = \DrevOps\VortexTooling\request('http://example.com/nonexistent-page-12345');

    $this->assertFalse($result['ok']);
    $this->assertEquals(404, $result['status']);
  }

  /**
   * Test request() with very short timeout triggers error.
   */
  public function testRequestTimeout(): void {
    // Use a very short timeout to a slow endpoint.
    $result = \DrevOps\VortexTooling\request('http://example.com:81/', [
      'timeout' => 1,
    ]);

    $this->assertFalse($result['ok']);
    $this->assertNotNull($result['error']);
  }

}
