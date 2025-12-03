<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Downloader;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\Git;
use DrevOps\VortexInstaller\Utils\Validator;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Download files from a local or remote Git repository using archive.
 */
class RepositoryDownloader implements RepositoryDownloaderInterface {

  const REF_HEAD = 'HEAD';

  const REF_STABLE = 'stable';

  /**
   * Constructs a new RepositoryDownloader instance.
   *
   * @param \GuzzleHttp\ClientInterface|null $httpClient
   *   Optional HTTP client for API calls (e.g., discovering releases).
   *   If not provided, a default Guzzle client will be created.
   * @param \DrevOps\VortexInstaller\Downloader\ArchiverInterface|null $archiver
   *   Optional Archiver instance for testing. If not provided, a default
   *   Archiver will be created.
   * @param \DrevOps\VortexInstaller\Utils\Git|null $git
   *   Optional Git instance for testing. If not provided, will be created
   *   when needed for local repository operations.
   * @param \DrevOps\VortexInstaller\Downloader\Downloader|null $fileDownloader
   *   Optional Downloader instance for downloading archive files.
   *   If not provided, a default Downloader will be created.
   */
  public function __construct(
    protected ?ClientInterface $httpClient = new Client(['timeout' => 30, 'connect_timeout' => 10]),
    protected ?ArchiverInterface $archiver = new Archiver(),
    protected ?Git $git = NULL,
    protected ?Downloader $fileDownloader = new Downloader(),
  ) {
  }

  public function download(string $repo, string $ref, ?string $dst = NULL): string {
    if (str_starts_with($repo, 'https://') || str_starts_with($repo, 'git@')) {
      $version = $this->downloadFromRemote($repo, $ref, $dst);
    }
    else {
      $version = $this->downloadFromLocal($repo, $ref, $dst);
    }

    if (!is_readable($dst . DIRECTORY_SEPARATOR . 'composer.json')) {
      throw new \RuntimeException('The downloaded repository does not contain a composer.json file.');
    }

    return $version;
  }

  public static function parseUri(string $src): array {
    if (str_starts_with($src, 'https://')) {
      if (!preg_match('#^(https://[^/]+/[^/]+/[^@]+)(?:@(.+))?$#', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid remote repository format: "%s".', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? static::REF_HEAD;
    }
    elseif (str_starts_with($src, 'git@')) {
      if (!preg_match('#^(git@[^:]+:[^/]+/[^@]+)(?:@(.+))?$#', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid remote repository format: "%s".', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? static::REF_HEAD;
    }
    elseif (str_starts_with($src, 'file://')) {
      if (!preg_match('#^file://(.+?)(?:@(.+))?$#', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid local repository format: "%s".', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? static::REF_HEAD;
    }
    else {
      if (!preg_match('#^(.+?)(?:@(.+))?$#', $src, $matches)) {
        throw new \RuntimeException(sprintf('Invalid local repository format: "%s".', $src));
      }
      $repo = rtrim($matches[1], '/');
      $ref = $matches[2] ?? static::REF_HEAD;
    }

    if ($ref != static::REF_STABLE && $ref != static::REF_HEAD && !Validator::gitCommitSha($ref) && !Validator::gitCommitShaShort($ref)) {
      throw new \RuntimeException(sprintf('Invalid reference format: "%s". Supported formats are: %s, %s, %s, %s.', $ref, static::REF_STABLE, static::REF_HEAD, '40-character commit hash', '7-character commit hash'));
    }

    return [$repo, $ref];
  }

  protected function downloadFromRemote(string $repo, string $ref, ?string $destination): string {
    if ($destination === NULL) {
      throw new \InvalidArgumentException('Destination cannot be null for remote downloads.');
    }
    $repo_url = str_ends_with($repo, '.git') ? substr($repo, 0, -4) : $repo;

    $version = $ref;
    if ($ref === RepositoryDownloader::REF_STABLE) {
      $ref = $this->discoverLatestReleaseRemote($repo_url);

      if ($ref === NULL) {
        throw new \RuntimeException(sprintf('Unable to discover the latest release for "%s".', $repo_url));
      }

      $version = $ref;
    }
    elseif ($ref === RepositoryDownloader::REF_HEAD) {
      $version = 'develop';
    }

    $url = sprintf('%s/archive/%s.tar.gz', $repo_url, $ref);

    $archive_path = $this->downloadArchive($url);
    $this->archiver->validate($archive_path);
    $this->archiver->extract($archive_path, $destination, TRUE);
    unlink($archive_path);

    return $version;
  }

  protected function downloadFromLocal(string $repo, string $ref, ?string $destination): string {
    if ($destination === NULL) {
      throw new \InvalidArgumentException('Destination cannot be null for local downloads.');
    }

    $ref = $ref === RepositoryDownloader::REF_STABLE ? RepositoryDownloader::REF_HEAD : $ref;
    $version = $ref;

    if ($ref === RepositoryDownloader::REF_HEAD) {
      if (!$this->git instanceof Git) {
        $this->git = new Git($repo);
      }
      $ref = $this->git->getLastShortCommitId();
      $version = 'develop';
    }

    $archive_path = $this->archiveFromLocal($repo, $ref);
    $this->archiver->validate($archive_path);
    $this->archiver->extract($archive_path, $destination, FALSE);
    unlink($archive_path);

    return $version;
  }

  protected function discoverLatestReleaseRemote(string $repo_url, ?string $release_prefix = NULL): ?string {
    $path = parse_url($repo_url, PHP_URL_PATH);
    if ($path === FALSE) {
      throw new \RuntimeException(sprintf('Invalid repository URL: "%s".', $repo_url));
    }

    $path = ltrim((string) $path, '/');

    $release_url = sprintf('https://api.github.com/repos/%s/releases', $path);

    $headers = ['User-Agent' => 'Vortex-Installer', 'Accept' => 'application/vnd.github.v3+json'];

    $github_token = Env::get('GITHUB_TOKEN');
    if ($github_token) {
      $headers['Authorization'] = sprintf('Bearer %s', $github_token);
    }

    try {
      $response = $this->httpClient->request('GET', $release_url, ['headers' => $headers]);
      $release_contents = $response->getBody()->getContents();
    }
    catch (RequestException $e) {
      throw new \RuntimeException(sprintf('Unable to download release information from "%s": %s', $release_url, $e->getMessage()), $e->getCode(), $e);
    }

    if ($release_contents === '' || $release_contents === '0') {
      $message = sprintf('Unable to download release information from "%s"%s.', $release_url, $github_token ? ' (GitHub token was used)' : '');
      throw new \RuntimeException($message);
    }

    $records = json_decode($release_contents, TRUE);

    foreach ($records as $record) {
      $tag_name = is_scalar($record['tag_name']) ? strval($record['tag_name']) : '';
      $is_draft = $record['draft'] ?? FALSE;

      if (!$is_draft && (!$release_prefix || str_starts_with($tag_name, $release_prefix))) {
        return $tag_name;
      }
    }

    return NULL;
  }

  /**
   * Download archive from URL to a temporary file.
   *
   * @param string $url
   *   The URL to download from.
   *
   * @return string
   *   Path to the downloaded temporary file.
   *
   * @throws \RuntimeException
   *   If download fails.
   */
  protected function downloadArchive(string $url): string {
    $temp_file = tempnam(sys_get_temp_dir(), 'vortex_archive_');
    if ($temp_file === FALSE) {
      throw new \RuntimeException('Unable to create temporary file for archive download.');
    }

    $headers = ['User-Agent' => 'Vortex-Installer'];

    $github_token = Env::get('GITHUB_TOKEN');
    if ($github_token) {
      $headers['Authorization'] = sprintf('Bearer %s', $github_token);
    }

    try {
      $this->fileDownloader->download($url, $temp_file, $headers);
    }
    catch (\RuntimeException $e) {
      if (file_exists($temp_file)) {
        unlink($temp_file);
      }
      throw new \RuntimeException(sprintf('Failed to download archive from: %s - %s', $url, $e->getMessage()), $e->getCode(), $e);
    }

    return $temp_file;
  }

  /**
   * Create archive from local Git repository.
   *
   * @param string $repo
   *   Path to the local repository.
   * @param string $ref
   *   Git reference to archive.
   *
   * @return string
   *   Path to the created temporary archive file.
   *
   * @throws \RuntimeException
   *   If archive creation fails.
   */
  protected function archiveFromLocal(string $repo, string $ref): string {
    if (!$this->git instanceof Git) {
      $this->git = new Git($repo);
    }

    $temp_file = sys_get_temp_dir() . '/vortex_local_archive_' . uniqid() . '.tar';

    try {
      $this->git->run('archive', '--format=tar', $ref, '-o', $temp_file);

      if (!file_exists($temp_file) || filesize($temp_file) === 0) {
        throw new \RuntimeException('Archive creation produced empty file.');
      }
    }
    catch (\Exception $e) {
      if (file_exists($temp_file)) {
        unlink($temp_file);
      }
      throw new \RuntimeException(sprintf('Failed to create archive from local repository: %s - %s', $repo, $e->getMessage()), $e->getCode(), $e);
    }

    return $temp_file;
  }

}
