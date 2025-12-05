<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Downloader;

use DrevOps\VortexInstaller\Downloader\Downloader;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(Downloader::class)]
class DownloaderTest extends UnitTestCase {

  public function testDownloadSuccess(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(200);
    $mock_http_client->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        'https://example.com/file.sql',
        $this->callback(fn(array $options): bool => isset($options['sink']) && isset($options['allow_redirects']) && $options['allow_redirects'] === TRUE)
      )
      ->willReturn($mock_response);

    $destination = self::$tmp . '/downloaded_file.sql';

    $downloader = new Downloader($mock_http_client);
    $downloader->download('https://example.com/file.sql', $destination);

    // If we got here without exception, the download was successful.
    $this->addToAssertionCount(1);
  }

  public function testDownloadFailure(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_http_client->method('request')
      ->willThrowException(new RequestException('Network error', $this->createMock(RequestInterface::class)));

    $destination = self::$tmp . '/downloaded_file.sql';

    $downloader = new Downloader($mock_http_client);

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('Failed to download file from https://example.com/file.sql: Network error');

    $downloader->download('https://example.com/file.sql', $destination);
  }

  public function testDownloadFollowsRedirects(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(200);

    $mock_http_client->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        $this->anything(),
        $this->callback(fn(array $options): bool => $options['allow_redirects'] === TRUE)
      )
      ->willReturn($mock_response);

    $destination = self::$tmp . '/downloaded_file.sql';

    $downloader = new Downloader($mock_http_client);
    $downloader->download('https://example.com/redirect', $destination);

    $this->addToAssertionCount(1);
  }

  public function testDownloadWithDefaultClient(): void {
    // Test that the class can be instantiated without providing an HTTP client.
    $downloader = new Downloader();
    $this->assertInstanceOf(Downloader::class, $downloader);
  }

  public function testDownloadWithCustomHeaders(): void {
    $mock_http_client = $this->createMock(ClientInterface::class);
    $mock_response = $this->createMock(ResponseInterface::class);
    $mock_response->method('getStatusCode')->willReturn(200);

    $custom_headers = [
      'Authorization' => 'Bearer token123',
      'X-Custom-Header' => 'custom-value',
    ];

    $mock_http_client->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        'https://example.com/file.sql',
        $this->callback(fn(array $options): bool => isset($options['sink'])
          && isset($options['allow_redirects'])
          && isset($options['headers'])
          && $options['headers'] === $custom_headers)
      )
      ->willReturn($mock_response);

    $destination = self::$tmp . '/downloaded_file.sql';

    $downloader = new Downloader($mock_http_client);
    $downloader->download('https://example.com/file.sql', $destination, $custom_headers);

    $this->addToAssertionCount(1);
  }

}
