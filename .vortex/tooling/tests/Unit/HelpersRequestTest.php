<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;

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
#[Group('helpers')]
class HelpersRequestTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    require_once __DIR__ . '/../../src/helpers.php';
  }

  public function testRequestGetReal(): void {
    $result = \DrevOps\VortexTooling\request_get('http://example.com/');

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
    $this->assertIsString($result['body']);
    $this->assertStringContainsString('Example Domain', $result['body']);
    $this->assertNull($result['error']);
  }

  public function testRequestGetWithHeaders(): void {
    $result = \DrevOps\VortexTooling\request_get(
      'http://example.com/',
      ['User-Agent: TestBot/1.0'],
      10
    );

    $this->assertTrue($result['ok']);
    $this->assertEquals(200, $result['status']);
  }

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

  public function testRequestNotFound(): void {
    $result = \DrevOps\VortexTooling\request('http://example.com/nonexistent-page-12345');

    $this->assertFalse($result['ok']);
    $this->assertEquals(404, $result['status']);
  }

  public function testRequestTimeout(): void {
    // Use a very short timeout to a slow endpoint.
    $result = \DrevOps\VortexTooling\request('http://example.com:81/', [
      'timeout' => 1,
    ]);

    $this->assertFalse($result['ok']);
    $this->assertNotNull($result['error']);
  }

  public function testSaveToStreamsToFile(): void {
    $input = self::$tmp . '/input.txt';
    $output = self::$tmp . '/output.txt';
    $content = str_repeat('Database dump content. ', 100);
    file_put_contents($input, $content);

    $result = \DrevOps\VortexTooling\request('file://' . $input, [
      'save_to' => $output,
    ]);

    $this->assertTrue($result['ok']);
    $this->assertFileExists($output);
    $this->assertEquals($content, file_get_contents($output));
    $this->assertEquals('', $result['body']);
  }

  public function testSaveToLargeFile(): void {
    $input = self::$tmp . '/large_input.bin';
    $output = self::$tmp . '/large_output.bin';
    // 1 MB of data to verify streaming handles non-trivial sizes.
    $content = random_bytes(1024 * 1024);
    file_put_contents($input, $content);

    $result = \DrevOps\VortexTooling\request('file://' . $input, [
      'save_to' => $output,
    ]);

    $this->assertTrue($result['ok']);
    $this->assertFileExists($output);
    $this->assertEquals(strlen($content), filesize($output));
    $this->assertEquals($content, file_get_contents($output));
    $this->assertEquals('', $result['body']);
  }

  public function testWithoutSaveToReturnsBody(): void {
    $input = self::$tmp . '/input.txt';
    $content = 'Small response body';
    file_put_contents($input, $content);

    $result = \DrevOps\VortexTooling\request('file://' . $input);

    $this->assertTrue($result['ok']);
    $this->assertEquals($content, $result['body']);
  }

}
