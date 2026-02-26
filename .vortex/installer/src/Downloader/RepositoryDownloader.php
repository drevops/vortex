<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Downloader;

use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use DrevOps\VortexInstaller\Utils\Git;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Download files from a local or remote Git repository using archive.
 */
class RepositoryDownloader implements RepositoryDownloaderInterface {

  const REF_HEAD = 'HEAD';

  const REF_STABLE = 'stable';

  const DEFAULT_REPO = 'https://github.com/drevops/vortex.git';

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

  public function download(Artifact $artifact, ?string $dst = NULL): string {
    if ($artifact->isRemote()) {
      $version = $this->downloadFromRemote($artifact, $dst);
    }
    else {
      $version = $this->downloadFromLocal($artifact, $dst);
    }

    if (!is_readable($dst . DIRECTORY_SEPARATOR . 'composer.json')) {
      throw new \RuntimeException('The downloaded repository does not contain a composer.json file.');
    }

    return $version;
  }

  /**
   * Validate repository and reference exist.
   *
   * @param \DrevOps\VortexInstaller\Downloader\Artifact $artifact
   *   The artifact to validate.
   *
   * @throws \RuntimeException
   *   If validation fails.
   */
  public function validate(Artifact $artifact): void {
    // Determine if this is a remote or local repository.
    if ($artifact->isRemote()) {
      // Remote repository.
      $repo_url = $artifact->getRepoUrl();

      // Validate repository exists.
      $this->validateRemoteRepositoryExists($repo_url);

      // Validate ref exists (skip for special refs).
      if ($artifact->getRef() !== self::REF_STABLE && $artifact->getRef() !== self::REF_HEAD) {
        $this->validateRemoteRefExists($repo_url, $artifact->getRef());
      }
    }
    else {
      // Local repository.
      // Validate repository exists.
      $this->validateLocalRepositoryExists($artifact->getRepo());

      // Validate ref exists (skip for HEAD).
      $actual_ref = $artifact->getRef() === self::REF_STABLE ? self::REF_HEAD : $artifact->getRef();
      if ($actual_ref !== self::REF_HEAD) {
        $this->validateLocalRefExists($artifact->getRepo(), $actual_ref);
      }
    }
  }

  protected function downloadFromRemote(Artifact $artifact, ?string $destination): string {
    if ($destination === NULL) {
      throw new \InvalidArgumentException('Destination cannot be null for remote downloads.');
    }
    $repo_url = $artifact->getRepoUrl();

    // Validate repository exists before proceeding.
    $this->validateRemoteRepositoryExists($repo_url);

    $version = $artifact->getRef();
    if ($artifact->getRef() === RepositoryDownloader::REF_STABLE) {
      $ref = $this->discoverLatestReleaseRemote($repo_url);

      if ($ref === NULL) {
        throw new \RuntimeException(sprintf('Unable to discover the latest release for "%s".', $repo_url));
      }

      $version = $ref;
    }
    elseif ($artifact->getRef() === RepositoryDownloader::REF_HEAD) {
      $ref = $artifact->getRef();
      $version = 'develop';
    }
    else {
      $ref = $artifact->getRef();
      // Validate ref exists for non-special refs.
      $this->validateRemoteRefExists($repo_url, $ref);
    }

    $url = sprintf('%s/archive/%s.tar.gz', $repo_url, $ref);

    $archive_path = $this->downloadArchive($url);
    $this->archiver->validate($archive_path);
    $this->archiver->extract($archive_path, $destination, TRUE);
    File::remove($archive_path);

    return $version;
  }

  protected function downloadFromLocal(Artifact $artifact, ?string $destination): string {
    if ($destination === NULL) {
      throw new \InvalidArgumentException('Destination cannot be null for local downloads.');
    }

    // Validate local repository exists.
    $this->validateLocalRepositoryExists($artifact->getRepo());

    $ref = $artifact->getRef() === RepositoryDownloader::REF_STABLE ? RepositoryDownloader::REF_HEAD : $artifact->getRef();
    $version = $ref;

    if ($ref === RepositoryDownloader::REF_HEAD) {
      if (!$this->git instanceof Git) {
        $this->git = new Git($artifact->getRepo());
      }
      $ref = $this->git->getLastShortCommitId();
      $version = 'develop';
    }
    else {
      // Validate ref exists for non-HEAD refs.
      $this->validateLocalRefExists($artifact->getRepo(), $ref);
    }

    $archive_path = $this->archiveFromLocal($artifact->getRepo(), $ref);
    $this->archiver->validate($archive_path);
    $this->archiver->extract($archive_path, $destination, FALSE);
    File::remove($archive_path);

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
        File::remove($temp_file);
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
        File::remove($temp_file);
      }
      throw new \RuntimeException(sprintf('Failed to create archive from local repository: %s - %s', $repo, $e->getMessage()), $e->getCode(), $e);
    }

    return $temp_file;
  }

  /**
   * Validate that a remote repository exists and is accessible.
   *
   * @param string $repo_url
   *   The repository URL (without .git extension).
   *
   * @throws \RuntimeException
   *   If the repository is not accessible.
   */
  protected function validateRemoteRepositoryExists(string $repo_url): void {
    $headers = ['User-Agent' => 'Vortex-Installer'];

    $github_token = Env::get('GITHUB_TOKEN');
    if ($github_token) {
      $headers['Authorization'] = sprintf('Bearer %s', $github_token);
    }

    try {
      // Try to access the repository root to verify it exists.
      $response = $this->httpClient->request('HEAD', $repo_url, ['headers' => $headers, 'http_errors' => FALSE]);
      $status_code = $response->getStatusCode();

      if ($status_code >= 400) {
        throw new \RuntimeException(sprintf('Repository not found or not accessible: "%s" (HTTP %d)', $repo_url, $status_code));
      }
    }
    catch (RequestException $e) {
      throw new \RuntimeException(sprintf('Unable to access repository: "%s" - %s', $repo_url, $e->getMessage()), $e->getCode(), $e);
    }
  }

  /**
   * Validate that a reference exists in a remote repository.
   *
   * @param string $repo_url
   *   The repository URL (without .git extension).
   * @param string $ref
   *   The git reference to validate.
   *
   * @throws \RuntimeException
   *   If the reference does not exist.
   */
  protected function validateRemoteRefExists(string $repo_url, string $ref): void {
    $archive_url = sprintf('%s/archive/%s.tar.gz', $repo_url, $ref);
    $headers = ['User-Agent' => 'Vortex-Installer'];

    $github_token = Env::get('GITHUB_TOKEN');
    if ($github_token) {
      $headers['Authorization'] = sprintf('Bearer %s', $github_token);
    }

    try {
      // Use HEAD request to check if the archive URL exists without
      // downloading.
      $response = $this->httpClient->request('HEAD', $archive_url, ['headers' => $headers, 'http_errors' => FALSE]);
      $status_code = $response->getStatusCode();

      if ($status_code === 404) {
        throw new \RuntimeException(sprintf('Reference "%s" not found in repository "%s"', $ref, $repo_url));
      }
      elseif ($status_code >= 400) {
        throw new \RuntimeException(sprintf('Unable to verify reference "%s" in repository "%s" (HTTP %d)', $ref, $repo_url, $status_code));
      }
    }
    catch (RequestException $e) {
      throw new \RuntimeException(sprintf('Unable to verify reference "%s" in repository "%s" - %s', $ref, $repo_url, $e->getMessage()), $e->getCode(), $e);
    }
  }

  /**
   * Validate that a local repository exists and is a valid git repository.
   *
   * @param string $repo
   *   The local repository path.
   *
   * @throws \RuntimeException
   *   If the repository does not exist or is not a valid git repository.
   */
  protected function validateLocalRepositoryExists(string $repo): void {
    if (!is_dir($repo)) {
      throw new \RuntimeException(sprintf('Local repository path does not exist: "%s"', $repo));
    }

    if (!is_dir($repo . '/.git')) {
      throw new \RuntimeException(sprintf('Path is not a git repository: "%s"', $repo));
    }
  }

  /**
   * Validate that a reference exists in a local repository.
   *
   * @param string $repo
   *   The local repository path.
   * @param string $ref
   *   The git reference to validate.
   *
   * @throws \RuntimeException
   *   If the reference does not exist.
   */
  protected function validateLocalRefExists(string $repo, string $ref): void {
    $repo_path = (string) realpath($repo);

    // Reinitialize Git instance if it doesn't exist or references a different
    // repository.
    if (!$this->git instanceof Git || $this->git->getRepositoryPath() !== $repo_path) {
      $this->git = new Git($repo);
    }

    try {
      // Use git rev-parse to check if the ref exists.
      $this->git->run('rev-parse', '--verify', $ref);
    }
    catch (\Exception $e) {
      throw new \RuntimeException(sprintf('Reference "%s" not found in local repository "%s"', $ref, $repo), $e->getCode(), $e);
    }
  }

}
