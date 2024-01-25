<?php

namespace DrevOps\DevTool\Tests\Unit\Utils;

use DrevOps\DevTool\Utils\Downloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * @coversDefaultClass \DrevOps\DevTool\Utils\Downloader
 */
class DownloaderTest extends TestCase {

  /**
   * @covers ::__construct
   * @covers ::download
   */
  public function testSuccessfulDownload(): void {
    $src = 'http://example.com/file.txt';
    $dst = 'path/to/local/file.txt';
    $content = 'file content';

    $response = new MockResponse($content, ['http_code' => 200]);
    $client = new MockHttpClient($response);

    $downloader = new Downloader($client);
    $result = $downloader->download($src, $dst);

    $this->assertEquals($dst, $result);
    $this->assertStringEqualsFile($dst, $content);
    unlink($dst);
  }

  /**
   * @covers ::__construct
   * @covers ::download
   */
  public function testFailedDownload(): void {
    $this->expectException(\Exception::class);

    $src = 'http://example.com/invalidfile.txt';
    $dst = 'path/to/local/file.txt';

    $response = new MockResponse('', ['http_code' => 404]);
    $client = new MockHttpClient($response);

    $downloader = new Downloader($client);
    $downloader->download($src, $dst);
  }

}
