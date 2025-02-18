<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use RuntimeException;

/**
 * Downloader class to download files from a local or remote Git repository using archive functionality.
 */
class Downloader {

  protected string $tmpDir;

  public function __construct() {
    $this->tmpDir = File::tmpdir();
  }

  public function download(string $src, ?string $dst = NULL): string {
    $dst = $dst ?? $this->tmpDir;

    [$repo, $ref] = $this->parseUri($src);

    if (str_starts_with($src, 'https://') || str_starts_with($src, 'git@')) {
      $this->downloadFromRemote($repo, $ref, $dst);
    }
    else {
      $this->downloadFromLocal($repo, $ref, $dst);
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
      $ref = $matches[2] ?? 'HEAD';
    }
    elseif (str_starts_with($src, 'file://')) {
      if (!preg_match('#^file://(.+?)(?:@(.+))?$#', $src, $matches)) {
        throw new RuntimeException(sprintf('Invalid remote repository format: "%s".', $src));
      }
      $repo = $matches[1];
      $ref = $matches[2] ?? 'HEAD';
    }
    else {
      if (!preg_match('#^(.+?)(?:@(.+))?$#', $src, $matches)) {
        throw new RuntimeException(sprintf('Invalid local repository format: "%s".', $src));
      }
      $repo = rtrim($matches[1], '/');
      $ref = $matches[2] ?? 'HEAD';

      return [$repo, $ref];
    }

    return [$repo, $ref];
  }

  protected function downloadFromRemote(string $repo, string $ref, string $destination): void {
    $url = sprintf('%s/archive/%s.tar.gz', $repo, $ref);
    $command = sprintf(
      'curl -sS -L "%s" | tar xzf - -C "%s" --strip 1',
      $url,
      $destination
    );

    if (!passthru($command)) {
      throw new RuntimeException(sprintf('Failed to download the remote archive: %s', $command));
    }
  }

  protected function downloadFromLocal(string $repo, string $ref, string $destination): void {
    $command = sprintf(
      'git --git-dir="%s/.git" --work-tree="%s" archive --format=tar "%s" | tar xf - -C "%s"',
      $repo,
      $repo,
      $ref,
      $destination
    );

    if (!passthru($command)) {
      throw new RuntimeException(sprintf('Failed to download the local archive: %s', $command));
    }
  }

  public static function makeUri(string $repo, string $ref): string {
    return sprintf('%s@%s', $repo, $ref);
  }
}
