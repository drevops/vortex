<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use DrevOps\VortexInstaller\Downloader\Downloader;

/**
 * File operations for the installation process.
 */
class FileManager {

  public function __construct(
    protected Config $config,
  ) {}

  /**
   * Prepare the destination directory.
   *
   * @return array<string>
   *   Array of status messages.
   */
  public function prepareDestination(): array {
    $messages = [];

    $destination = $this->config->getDestination();
    if (!is_dir($destination)) {
      $destination = File::mkdir($destination);
      $messages[] = sprintf('Created directory "%s".', $destination);
    }

    if (!is_readable($destination . '/.git')) {
      $messages[] = sprintf('Initializing a new Git repository in directory "%s".', $destination);
      passthru(sprintf('git --work-tree="%s" --git-dir="%s/.git" init > /dev/null', $destination, $destination));

      if (!File::exists($destination . '/.git')) {
        throw new \RuntimeException(sprintf('Unable to initialize Git repository in directory "%s".', $destination));
      }
    }

    return $messages;
  }

  public function copyFiles(): void {
    $src = $this->config->get(Config::TMP);
    $destination = $this->config->getDestination();

    // Symlink ordering prevents copying files one-by-one into the destination
    // directory. Instead, all ignored files and empty directories are removed
    // to make the src directory "clean", and then the whole directory is
    // copied recursively.
    $all = File::scandir($src, File::ignoredPaths(), TRUE);
    $files = File::scandir($src);
    $valid_files = File::scandir($src, File::ignoredPaths());
    $dirs = array_diff($all, $valid_files);
    $ignored_files = array_diff($files, $valid_files);

    foreach ($valid_files as $valid_file) {
      $relative_file = str_replace($src . DIRECTORY_SEPARATOR, '.' . DIRECTORY_SEPARATOR, (string) $valid_file);

      if (File::isInternal($relative_file)) {
        File::remove($valid_file);
      }
    }

    foreach ($ignored_files as $ignored_file) {
      if (is_readable($ignored_file)) {
        File::remove($ignored_file);
      }
    }

    foreach ($dirs as $dir) {
      File::rmdirIfEmpty($dir);
    }

    if (is_dir($src) && !File::dirIsEmpty($src)) {
      File::copy($src, $destination);
    }

    // Special case for .env.local as it may exist.
    if (!file_exists($destination . '/.env.local') && file_exists($destination . '/.env.local.example')) {
      File::copy($destination . '/.env.local.example', $destination . '/.env.local');
    }

    $this->removeObsoletePaths();
  }

  /**
   * Remove obsolete paths from previous Vortex versions.
   *
   * Removes paths that previous Vortex versions placed in the destination but
   * the current version no longer ships. Runs after copyFiles() so legacy
   * artifacts do not linger across upgrades.
   */
  public function removeObsoletePaths(): void {
    $destination = $this->config->getDestination();

    // 'scripts/vortex/' was the location of shipped Vortex scripts before
    // they were extracted into the 'drevops/vortex-tooling' Composer package.
    // Consumer projects updated from older Vortex versions still have it.
    $obsolete = [
      'scripts/vortex',
    ];

    foreach ($obsolete as $relative) {
      $path = $destination . '/' . $relative;
      if (file_exists($path)) {
        File::remove($path);
      }
    }
  }

  /**
   * Prepare demo content if in demo mode.
   *
   * @param \DrevOps\VortexInstaller\Downloader\Downloader $downloader
   *   The file downloader.
   *
   * @return array|string
   *   Array of messages or a single message.
   */
  public function prepareDemo(Downloader $downloader): array|string {
    if (empty($this->config->get(Config::IS_DEMO))) {
      return 'Not a demo mode.';
    }

    if (!empty($this->config->get(Config::IS_DEMO_DB_FETCH_SKIP))) {
      return sprintf('%s is set. Skipping demo database fetch.', Config::IS_DEMO_DB_FETCH_SKIP);
    }

    // Reload variables from destination's .env.
    Env::putFromDotenv($this->config->getDestination() . '/.env');

    $url = Env::get('VORTEX_FETCH_DB_URL');
    if (empty($url)) {
      return 'No database fetch URL provided. Skipping demo database fetch.';
    }

    $data_dir = $this->config->getDestination() . '/' . Env::get('VORTEX_DB_DIR', './.data');
    $db_file = Env::get('VORTEX_DB_FILE', 'db.sql');

    if (file_exists($data_dir . '/' . $db_file)) {
      return 'Database dump file already exists. Skipping demo database fetch.';
    }

    $messages = [];
    if (!file_exists($data_dir)) {
      $data_dir = File::mkdir($data_dir);
      $messages[] = sprintf('Created data directory "%s".', $data_dir);
    }

    $destination = $data_dir . '/' . $db_file;
    $downloader->download($url, $destination);

    $messages[] = sprintf('No database dump file was found in "%s" directory.', $data_dir);
    $messages[] = sprintf('Fetched demo database from %s.', $url);

    return $messages;
  }

}
