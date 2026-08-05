<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Downloader;

use DrevOps\VortexInstaller\Runner\ProcessRunner;
use DrevOps\VortexInstaller\Runner\RunnerInterface;
use DrevOps\VortexInstaller\Utils\File;
use PhpZip\ZipFile;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Handles archive operations (detection, validation, extraction).
 */
class Archiver implements ArchiverInterface {

  /**
   * {@inheritdoc}
   */
  public function detectFormat(string $archive_path): ?string {
    $handle = @fopen($archive_path, 'rb');
    if ($handle === FALSE) {
      throw new \RuntimeException(sprintf('Unable to read archive file: %s', $archive_path));
    }

    $header = fread($handle, 512);
    fclose($handle);

    if ($header === FALSE) {
      throw new \RuntimeException(sprintf('Failed to read archive file: %s', $archive_path));
    }

    if (strlen($header) >= 2 && str_starts_with($header, "\x1f\x8b")) {
      return 'tar.gz';
    }

    if (strlen($header) >= 512) {
      $tar_magic = substr($header, 257, 5);
      if ($tar_magic === 'ustar' || $tar_magic === '00000') {
        return 'tar';
      }
    }

    if (strlen($header) >= 4 && str_starts_with($header, "\x50\x4b\x03\x04")) {
      return 'zip';
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(string $archive_path): void {
    if (!file_exists($archive_path)) {
      throw new \RuntimeException(sprintf('Archive file does not exist: %s', $archive_path));
    }

    if (filesize($archive_path) === 0) {
      throw new \RuntimeException('Archive is empty.');
    }

    $format = $this->detectFormat($archive_path);

    if ($format === NULL) {
      throw new \RuntimeException('File does not appear to be a valid archive (supported: tar.gz, tar, zip).');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extract(string $archive_path, string $destination, bool $strip_first_level = FALSE): void {
    $format = $this->detectFormat($archive_path);

    switch ($format) {
      case 'tar.gz':
      case 'tar':
        $this->extractTar($archive_path, $destination, $strip_first_level);
        break;

      case 'zip':
        $this->extractZip($archive_path, $destination, $strip_first_level);
        break;

      default:
        throw new \RuntimeException('Unsupported archive format: ' . $format);
    }
  }

  /**
   * Extract tar or tar.gz archive.
   *
   * @param string $archive_path
   *   Path to the archive file.
   * @param string $destination
   *   Destination directory.
   * @param bool $strip_first_level
   *   Whether to strip first directory level.
   *
   * @throws \RuntimeException
   *   If extraction fails.
   */
  protected function extractTar(string $archive_path, string $destination, bool $strip_first_level): void {
    $temp_dir = NULL;
    try {
      $temp_dir = $strip_first_level ? File::tmpdir() : $destination;

      // Use tar command to preserve symlinks (PharData doesn't preserve them).
      $runner = new ProcessRunner();
      $runner->getLogger()->disable();
      $runner->run('tar', args: ['-xf', $archive_path, '-C', $temp_dir], output: new NullOutput());

      if ($runner->getExitCode() !== RunnerInterface::EXIT_SUCCESS) {
        $output = $runner->getOutput(as_array: TRUE);
        throw new \RuntimeException(sprintf('tar command failed: %s', is_array($output) ? implode(PHP_EOL, $output) : $output));
      }

      if ($strip_first_level) {
        $this->stripDirectoryLevel($temp_dir, $destination);
      }
    }
    catch (\Exception $e) {
      throw new \RuntimeException(sprintf('Failed to extract tar archive to: %s - %s', $destination, $e->getMessage()), $e->getCode(), $e);
    }
    finally {
      if ($strip_first_level && is_dir($temp_dir)) {
        File::remove($temp_dir);
      }
    }
  }

  /**
   * Extract ZIP archive.
   *
   * @param string $archive_path
   *   Path to the archive file.
   * @param string $destination
   *   Destination directory.
   * @param bool $strip_first_level
   *   Whether to strip first directory level.
   *
   * @throws \RuntimeException
   *   If extraction fails.
   */
  protected function extractZip(string $archive_path, string $destination, bool $strip_first_level): void {
    $temp_dir = NULL;
    try {
      $zip = new ZipFile();
      $zip->openFile($archive_path);

      if ($strip_first_level) {
        $temp_dir = File::tmpdir();

        $zip->extractTo($temp_dir);

        $this->stripDirectoryLevel($temp_dir, $destination);
      }
      else {
        $zip->extractTo($destination);
      }

      $zip->close();
    }
    catch (\Exception $e) {
      throw new \RuntimeException(sprintf('Failed to extract ZIP archive to: %s - %s', $destination, $e->getMessage()), $e->getCode(), $e);
    }
    finally {
      if ($strip_first_level && is_dir($temp_dir)) {
        File::remove($temp_dir);
      }
    }
  }

  /**
   * Strip the first directory level from extracted archive.
   *
   * @param string $source
   *   Source directory with single top-level directory.
   * @param string $destination
   *   Destination directory for contents.
   *
   * @throws \RuntimeException
   *   If source doesn't have exactly one top-level directory.
   */
  protected function stripDirectoryLevel(string $source, string $destination): void {
    $entries = scandir($source);
    $entries = array_diff($entries, ['.', '..']);

    if (count($entries) !== 1) {
      throw new \RuntimeException('Expected single top-level directory in archive.');
    }

    $top_dir = reset($entries);
    $source_path = $source . '/' . $top_dir;

    File::copy($source_path, $destination);
  }

}
