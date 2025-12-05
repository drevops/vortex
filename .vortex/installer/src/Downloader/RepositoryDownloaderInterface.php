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
   * @param \DrevOps\VortexInstaller\Downloader\Artifact $artifact
   *   The artifact to download (contains repository and reference).
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
  public function download(Artifact $artifact, ?string $dst = NULL): string;

}
