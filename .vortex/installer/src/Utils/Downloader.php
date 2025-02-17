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
    $this->tmpDir = File::createTempdir();
  }

  public function download(string $src, ?string $dst = NULL): string {
    $dst = $dst ?? $this->tmpDir;

    if ($this->isRemoteRepo($src)) {
      [$org, $repo, $ref] = $this->parseRemoteRepo($src);
      $this->downloadFromRemote($org, $repo, $ref, $dst);
    }
    else {
      [$repo, $ref] = $this->parseLocalRepo($src);
      $this->downloadFromLocal($repo, $ref, $dst);
    }

    return $this->tmpDir;
  }

  protected function isRemoteRepo(string $src): bool {
    return str_starts_with($src, 'https://') || str_starts_with($src, 'git@');
  }

  protected function parseRemoteRepo(string $src): array {
    if (!preg_match('#^https://github.com/([^/]+)/([^@]+)@(.+)$#', $src, $matches)) {
      throw new RuntimeException(sprintf('Invalid remote repository format: "%s".', $src));
    }

    return [$matches[1], $matches[2], $matches[3]];
  }

  protected function parseLocalRepo(string $src): array {
    if (!preg_match('#^(.+?)(?:@(.+))?$#', $src, $matches)) {
      throw new RuntimeException(sprintf('Invalid local repository format: "%s".', $src));
    }
    $repo = rtrim($matches[1], '/');
    $ref = $matches[2] ?? 'HEAD';

    return [$repo, $ref];
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

  protected function downloadFromRemote(string $org, string $repo, string $ref, string $destination): void {
    $url = sprintf('https://github.com/%s/%s/archive/%s.tar.gz', $org, $repo, $ref);
    $command = sprintf(
      'curl -sS -L "%s" | tar xzf - -C "%s" --strip 1',
      $url,
      $destination
    );

    if (!passthru($command)) {
      throw new RuntimeException(sprintf('Failed to download the remote archive: %s', $command));
    }
  }

}
