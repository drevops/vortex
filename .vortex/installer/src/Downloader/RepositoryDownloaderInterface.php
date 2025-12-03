<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Downloader;

/**
 * Interface for downloading files from local or remote Git repositories.
 */
interface RepositoryDownloaderInterface {

  /**
   * Downloads a repository archive from a local or remote source.
   *
   * @param string $repo
   *   The repository URL or local path.
   * @param string $ref
   *   The reference to download (commit hash, HEAD, or stable).
   * @param string|null $dst
   *   The destination directory. If NULL, a temporary directory will be used
   *   for local repositories.
   *
   * @return string
   *   The version/reference that was downloaded.
   *
   * @throws \RuntimeException
   *   If the download fails or the repository is invalid.
   * @throws \InvalidArgumentException
   *   If the destination is null for remote downloads.
   */
  public function download(string $repo, string $ref, ?string $dst = NULL): string;

  /**
   * Parses a URI into repository and reference components.
   *
   * @param string $src
   *   The source URI (e.g., "https://github.com/user/repo@ref").
   *
   * @return array
   *   An array with two elements: [$repo, $ref].
   *
   * @throws \RuntimeException
   *   If the URI format is invalid or the reference format is not supported.
   */
  public static function parseUri(string $src): array;

}
