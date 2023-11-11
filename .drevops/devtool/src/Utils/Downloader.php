<?php

namespace DrevOps\DevTool\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class Downloader.
 *
 * Download files.
 *
 * @package DrevOps\DevTool\Utils
 */
class Downloader {

  /**
   * Http client.
   *
   * @var \Symfony\Contracts\HttpClient\HttpClientInterface
   */
  protected HttpClientInterface $httpClient;

  /**
   * Filesystem.
   */
  protected Filesystem $fs;

  /**
   * Downloader constructor.
   */
  public function __construct(HttpClientInterface $client = NULL, Filesystem $fs = NULL) {
    $this->httpClient = $client ?: HttpClient::create();
    $this->fs = $fs ?: new Filesystem();
  }

  /**
   * Download file.
   *
   * @param string $src
   *   Source URL.
   * @param string $dst
   *   Destination path.
   */
  public function download(string $src, string $dst): string {
    $response = $this->httpClient->request('GET', $src);

    if ($response->getStatusCode() !== 200) {
      throw new \Exception(sprintf('Failed to download the file from %s.', $src));
    }

    $this->fs->appendToFile($dst, $response->getContent());

    return $dst;
  }

}
