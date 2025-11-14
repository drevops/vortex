<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Downloader;

/**
 * Interface for archive operations.
 */
interface ArchiverInterface {

  /**
   * Detect the format of an archive file.
   *
   * @param string $archive_path
   *   Path to the archive file.
   *
   * @return string|null
   *   The detected format ('tar.gz', 'tar', or 'zip'), or NULL if unknown.
   *
   * @throws \RuntimeException
   *   If the file cannot be read.
   */
  public function detectFormat(string $archive_path): ?string;

  /**
   * Validate archive file (supports tar.gz, tar, and zip formats).
   *
   * @param string $archive_path
   *   Path to the archive file.
   *
   * @throws \RuntimeException
   *   If validation fails.
   */
  public function validate(string $archive_path): void;

  /**
   * Extract archive to destination directory.
   *
   * @param string $archive_path
   *   Path to the archive file.
   * @param string $destination
   *   Destination directory.
   * @param bool $strip_first_level
   *   Whether to strip the first directory level (default: FALSE).
   *
   * @throws \RuntimeException
   *   If extraction fails.
   */
  public function extract(string $archive_path, string $destination, bool $strip_first_level = FALSE): void;

}
