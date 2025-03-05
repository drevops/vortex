<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use RuntimeException;

/**
 * Downloader class to download files from a local or remote Git repository using archive functionality.
 */
class Downloader {

  public function download(string $src, string $dst = NULL): string {
    [$repo, $ref] = $this->parseUri($src);

    if (str_starts_with($src, 'https://') || str_starts_with($src, 'git@')) {
      $this->downloadFromRemote($repo, $ref, $dst);
    }
    else {
      $this->downloadFromLocal($repo, $ref, $dst);
    }

    if (!is_readable($dst . DIRECTORY_SEPARATOR . 'composer.json')) {
      throw new \RuntimeException(sprintf('The downloaded repository does not contain a composer.json file: %s', $src));
    }

    return $dst;
  }

  public static function parseUri(string $src) {
    if (str_starts_with($src, 'https://')) {
      if (!preg_match('#^(https://[^/]+/[^/]+/[^@]+)(?:@(.+))?$#', $src, $matches)) {
        throw new RuntimeException(sprintf('Invalid remote repository format: "%s".', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? 'HEAD';
    }
    elseif (str_starts_with($src, 'git@')) {
      if (!preg_match('#^(git@[^:]+:[^/]+/[^@]+)(?:@(.+))?$#', $src, $matches)) {
        throw new RuntimeException(sprintf('Invalid remote repository format: "%s".', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? 'stable';
    }
    elseif (str_starts_with($src, 'file://')) {
      if (!preg_match('#^file://(.+?)(?:@(.+))?$#', $src, $matches)) {
        throw new RuntimeException(sprintf('Invalid remote repository format: "%s".', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? 'stable';
    }
    else {
      if (!preg_match('#^(.+?)(?:@(.+))?$#', $src, $matches)) {
        throw new RuntimeException(sprintf('Invalid local repository format: "%s".', $src));
      }
      $repo = rtrim($matches[1], '/');
      $ref = $matches[2] ?? 'stable';

      return [$repo, $ref];
    }

    return [$repo, $ref];
  }

  public static function makeUri(string $repo, string $ref): string {
    return sprintf('%s@%s', $repo, $ref);
  }

  protected function downloadFromRemote(string $repo, string $ref, string $destination): void {
    $repo_url = str_ends_with($repo, '.git') ? substr($repo, 0, -4) : $repo;

    if ($ref == 'stable') {
      $ref = $this->discoverLatestRelease($repo_url);
    }

    $url = sprintf('%s/archive/%s.tar.gz', $repo_url, $ref);
    $command = sprintf(
      'curl -sS -L "%s" | tar xzf - -C "%s" --strip 1',
      $url,
      $destination
    );

    if (passthru($command) === FALSE) {
      throw new RuntimeException(sprintf('Failed to download the remote archive: %s', $command));
    }
  }

  protected function downloadFromLocal(string $repo, string $ref, string $destination): void {
    if ($ref == 'stable') {
      $ref = 'HEAD';
    }

    $command = sprintf(
      'git --git-dir="%s/.git" --work-tree="%s" archive --format=tar "%s" | tar xf - -C "%s"',
      $repo,
      $repo,
      $ref,
      $destination
    );

    if (passthru($command) === FALSE) {
      throw new RuntimeException(sprintf('Failed to download the local archive: %s', $command));
    }
  }

  protected function discoverLatestRelease(string $repo_url, ?string $release_prefix = NULL): ?string {
    $path = parse_url($repo_url, PHP_URL_PATH);
    $path = ltrim($path, '/');

    $release_url = sprintf('https://api.github.com/repos/%s/releases', $path);
    $release_contents = file_get_contents($release_url, FALSE, stream_context_create([
      'http' => ['method' => 'GET', 'header' => ['User-Agent: PHP']],
    ]));

    if (!$release_contents) {
      throw new \RuntimeException(sprintf('Unable to download release information from "%s".', $release_url));
    }

    $records = json_decode($release_contents, TRUE);

    if (!$release_prefix) {
      return is_scalar($records[0]['tag_name']) ? strval($records[0]['tag_name']) : NULL;
    }

    foreach ($records as $record) {
      $tag_name = is_scalar($record['tag_name']) ? strval($record['tag_name']) : '';
      if (str_contains($tag_name, $release_prefix)) {
        return $tag_name;
      }
    }

    return NULL;
  }

}
