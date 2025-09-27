<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

/**
 * Download files from a local or remote Git repository using archive.
 */
class Downloader {

  const REF_HEAD = 'HEAD';

  const REF_STABLE = 'stable';

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
    if ($ref === Downloader::REF_STABLE) {
      $ref = $this->discoverLatestReleaseRemote($repo_url);

      if ($ref === NULL) {
        throw new \RuntimeException(sprintf('Unable to discover the latest release for "%s".', $repo_url));
      }

      $version = $ref;
    }
    elseif ($ref === Downloader::REF_HEAD) {
      $version = 'develop';
    }

    // @todo Handle Downloader::REF_HEAD.
    $url = sprintf('%s/archive/%s.tar.gz', $repo_url, $ref);

    $archive_path = $this->downloadArchive($url);
    $this->validateArchive($archive_path);
    $this->extractArchive($archive_path, $destination, TRUE);
    unlink($archive_path);

    return $version;
  }

  protected function downloadFromLocal(string $repo, string $ref, ?string $destination): string {
    if ($destination === NULL) {
      throw new \InvalidArgumentException('Destination cannot be null for local downloads.');
    }

    // Local download does not support version discovery, but it still supports
    // downloading from a specific ref.
    $ref = $ref === Downloader::REF_STABLE ? Downloader::REF_HEAD : $ref;
    $version = $ref;

    if ($ref === Downloader::REF_HEAD) {
      $git = new Git($repo);
      $ref = $git->getLastShortCommitId();
      $version = 'develop';
    }

    $archive_path = $this->archiveFromLocal($repo, $ref);
    $this->validateArchive($archive_path);
    $this->extractArchive($archive_path, $destination, FALSE);
    unlink($archive_path);

    return $version;
  }

  protected function discoverLatestReleaseRemote(string $repo_url, ?string $release_prefix = NULL): ?string {
    $path = parse_url($repo_url, PHP_URL_PATH);
    if ($path === FALSE) {
      throw new \RuntimeException(sprintf('Invalid repository URL: "%s".', $repo_url));
    }

    $path = ltrim($path, '/');

    $release_url = sprintf('https://api.github.com/repos/%s/releases', $path);

    $headers = [
      'User-Agent: Vortex-Installer',
      'Accept: application/vnd.github.v3+json',
    ];

    // Add GitHub token authentication if available.
    $github_token = Env::get('GITHUB_TOKEN');
    if ($github_token) {
      $headers[] = sprintf('Authorization: Bearer %s', $github_token);
    }

    $release_contents = file_get_contents($release_url, FALSE, stream_context_create([
      'http' => [
        'method' => 'GET',
        'header' => implode("\r\n", $headers),
      ],
    ]));

    if (!$release_contents) {
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

    // Build curl command with headers.
    $headers = [
      'User-Agent: Vortex-Installer',
    ];

    $github_token = Env::get('GITHUB_TOKEN');
    if ($github_token) {
      $headers[] = sprintf('Authorization: Bearer %s', $github_token);
    }

    $header_args = '';
    foreach ($headers as $header) {
      $header_args .= sprintf(' -H %s', escapeshellarg($header));
    }

    $command = sprintf(
      'curl -sS -L%s -o %s %s',
      $header_args,
      escapeshellarg($temp_file),
      escapeshellarg($url)
    );

    if (passthru($command) === FALSE) {
      unlink($temp_file);
      throw new \RuntimeException(sprintf('Failed to download archive from: %s', $url));
    }

    return $temp_file;
  }

  /**
   * Validate archive file (supports both gzip and tar formats).
   *
   * @param string $archive_path
   *   Path to the archive file.
   *
   * @throws \RuntimeException
   *   If validation fails.
   */
  protected function validateArchive(string $archive_path): void {
    if (!file_exists($archive_path)) {
      throw new \RuntimeException(sprintf('Archive file does not exist: %s', $archive_path));
    }

    if (filesize($archive_path) === 0) {
      throw new \RuntimeException('Archive is empty.');
    }

    $file_handle = fopen($archive_path, 'rb');
    if ($file_handle === FALSE) {
      throw new \RuntimeException(sprintf('Unable to read archive file: %s', $archive_path));
    }

    // Read first few bytes to determine format.
    $header = fread($file_handle, 512);
    fclose($file_handle);
    if ($header === FALSE) {
      throw new \RuntimeException(sprintf('Failed to read archive file: %s', $archive_path));
    }

    // Check for gzip format (remote archives)
    if (strlen($header) >= 2) {
      $gzip_magic = substr($header, 0, 2);
      if ($gzip_magic === "\x1f\x8b") {
        // Valid gzip archive.
        return;
      }
    }

    // Check for tar format (local archives)
    if (strlen($header) >= 512) {
      $tar_magic = substr($header, 257, 5);
      if ($tar_magic === "ustar" || $tar_magic === "00000") {
        // Valid tar archive.
        return;
      }
    }

    throw new \RuntimeException('File does not appear to be a valid gzip or tar archive.');
  }

  /**
   * Extract archive to destination directory.
   *
   * @param string $archive_path
   *   Path to the archive file.
   * @param string $destination
   *   Destination directory.
   * @param bool $is_remote
   *   Whether this is a remote archive (affects extraction options).
   *
   * @throws \RuntimeException
   *   If extraction fails.
   */
  protected function extractArchive(string $archive_path, string $destination, bool $is_remote = TRUE): void {
    // Detect archive format.
    $file_handle = fopen($archive_path, 'rb');
    if ($file_handle === FALSE) {
      throw new \RuntimeException(sprintf('Unable to read archive file: %s', $archive_path));
    }

    $header = fread($file_handle, 512);
    fclose($file_handle);
    if ($header === FALSE) {
      throw new \RuntimeException(sprintf('Failed to read archive file: %s', $archive_path));
    }

    $is_gzipped = FALSE;
    if (strlen($header) >= 2) {
      $gzip_magic = substr($header, 0, 2);
      $is_gzipped = ($gzip_magic === "\x1f\x8b");
    }

    // Build tar command based on format and source.
    $tar_flags = 'xf';
    if ($is_gzipped) {
      $tar_flags = 'xzf';
    }

    $command = sprintf('tar %s %s -C %s', $tar_flags, escapeshellarg($archive_path), escapeshellarg($destination));

    // Add --strip 1 for remote archives (they have extra directory level)
    if ($is_remote) {
      $command .= ' --strip 1';
    }

    if (passthru($command) === FALSE) {
      throw new \RuntimeException(sprintf('Failed to extract archive to: %s', $destination));
    }
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
    $temp_file = tempnam(sys_get_temp_dir(), 'vortex_local_archive_');
    if ($temp_file === FALSE) {
      throw new \RuntimeException('Unable to create temporary file for local archive.');
    }

    $command = sprintf(
      'git --git-dir=%s --work-tree=%s archive --format=tar %s -o %s',
      escapeshellarg($repo . '/.git'),
      escapeshellarg($repo),
      escapeshellarg($ref),
      escapeshellarg($temp_file)
    );

    if (passthru($command) === FALSE) {
      unlink($temp_file);
      throw new \RuntimeException(sprintf('Failed to create archive from local repository: %s', $repo));
    }

    return $temp_file;
  }

}
