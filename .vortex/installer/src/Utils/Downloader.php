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
        throw new \RuntimeException(sprintf('Invalid remote repository format: "%s".', $src));
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

    if ($ref != static::REF_STABLE && $ref != static::REF_HEAD && !Validator::gitCommit($ref) && !Validator::gitShortCommit($ref)) {
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
    $command = sprintf(
      'curl -sS -L "%s" | tar xzf - -C "%s" --strip 1',
      $url,
      $destination
    );

    if (passthru($command) === FALSE) {
      throw new \RuntimeException(sprintf('Failed to download the remote archive: %s', $command));
    }

    return $version;
  }

  protected function downloadFromLocal(string $repo, string $ref, ?string $destination): string {
    if ($destination === NULL) {
      throw new \InvalidArgumentException('Destination cannot be null for local downloads.');
    }

    // Local download does not support version discovery.
    $ref = $ref === Downloader::REF_STABLE ? Downloader::REF_HEAD : $ref;
    $version = $ref;

    if ($ref === Downloader::REF_HEAD) {
      $git = new Git($repo);
      $ref = $git->getLastShortCommitId();
      $version = 'develop';
    }

    $command = sprintf(
      'git --git-dir="%s/.git" --work-tree="%s" archive --format=tar "%s" | tar xf - -C "%s"',
      $repo,
      $repo,
      $ref,
      $destination
    );

    if (passthru($command) === FALSE) {
      throw new \RuntimeException(sprintf('Failed to download the local archive: %s', $command));
    }

    return $version;
  }

  protected function discoverLatestReleaseRemote(string $repo_url, ?string $release_prefix = NULL): ?string {
    $path = parse_url($repo_url, PHP_URL_PATH);
    if ($path === FALSE) {
      throw new \RuntimeException(sprintf('Invalid repository URL: "%s".', $repo_url));
    }

    $path = ltrim($path, '/');

    $release_url = sprintf('https://api.github.com/repos/%s/releases', $path);

    $headers = ['User-Agent: PHP'];

    // Add GitHub token authentication if available.
    $github_token = Env::get('GITHUB_TOKEN');
    if ($github_token) {
      $headers[] = sprintf('Authorization: Bearer %s', $github_token);
    }

    $release_contents = file_get_contents($release_url, FALSE, stream_context_create([
      'http' => ['method' => 'GET', 'header' => $headers],
    ]));

    if (!$release_contents) {
      throw new \RuntimeException(sprintf('Unable to download release information from "%s".', $release_url));
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

}
