<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Downloader;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Download files from URLs using HTTP.
 */
class Downloader {

  /**
   * Constructs a new Downloader instance.
   *
   * @param \GuzzleHttp\ClientInterface|null $httpClient
   *   Optional HTTP client for testing. If not provided, a default Guzzle
   *   client will be created.
   */
  public function __construct(
    protected ?ClientInterface $httpClient = new Client(['timeout' => 30, 'connect_timeout' => 10]),
  ) {
  }

  /**
   * Download a file from a URL to a specified destination path.
   *
   * @param string $url
   *   The URL to download from.
   * @param string $destination
   *   The destination file path.
   * @param array<string, string> $headers
   *   Optional HTTP headers to include in the request.
   *
   * @throws \RuntimeException
   *   If download fails.
   */
  public function download(string $url, string $destination, array $headers = []): void {
    $options = [
      'sink' => $destination,
      'allow_redirects' => TRUE,
    ];

    if (!empty($headers)) {
      $options['headers'] = $headers;
    }

    try {
      $this->httpClient->request('GET', $url, $options);
    }
    catch (RequestException $e) {
      throw new \RuntimeException(sprintf('Failed to download file from %s: %s', $url, $e->getMessage()), $e->getCode(), $e);
    }
  }

}
