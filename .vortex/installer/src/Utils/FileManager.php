<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use DrevOps\VortexInstaller\Downloader\Downloader;

/**
 * File operations for the installation process.
 */
class FileManager {

  /**
   * Path of the template's own harness, which is never shipped.
   */
  const HARNESS_DIR = '.vortex';

  /**
   * Paths shipped by the downloaded template, relative to its root.
   *
   * @var array<string>
   */
  protected array $templatePaths = [];

  public function __construct(
    protected Config $config,
  ) {}

  /**
   * Record the paths of the freshly downloaded template.
   *
   * Taken before the handlers process the staged copy, so that whatever they
   * remove can later be identified as the paths the selection excludes.
   */
  public function snapshotTemplate(): void {
    $this->templatePaths = $this->relativePaths($this->config->get(Config::TMP));
  }

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

      // The destination arrives from a CLI option, the environment or a config
      // file, so it is escaped rather than interpolated into the shell string.
      $command = sprintf('git --work-tree=%s --git-dir=%s init > /dev/null', escapeshellarg($destination), escapeshellarg($destination . '/.git'));
      passthru($command, $exit_code);

      if ($exit_code !== 0 || !File::exists($destination . '/.git')) {
        throw new \RuntimeException(sprintf('Unable to initialize Git repository in directory "%s".', $destination));
      }
    }

    return $messages;
  }

  public function copyFiles(): void {
    $src = $this->config->get(Config::TMP);
    $destination = $this->config->getDestination();

    // Handlers strip the paths excluded by the current selection from the
    // staged copy, so what the template shipped but the staged copy no longer
    // holds is exactly that exclusion set.
    $excluded = array_diff($this->templatePaths, $this->relativePaths($src));

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

    $this->removeExcludedPaths($excluded);
    $this->removeObsoletePaths();
  }

  /**
   * Remove paths excluded by the current selection from the destination.
   *
   * The staged copy is overlaid onto the destination without a delete pass, so
   * a path the selection drops would otherwise survive from a previous
   * install and keep being detected as an active feature. Only projects
   * already running Vortex are pruned: in any other destination a matching
   * path belongs to that project rather than to a previous install.
   *
   * @param array<string> $paths
   *   Template-relative paths absent from the staged copy.
   */
  protected function removeExcludedPaths(array $paths): void {
    if (!$this->config->isVortexProject()) {
      return;
    }

    $destination = $this->config->getDestination();
    $dirs = [];

    foreach ($paths as $path) {
      // The harness never ships, so a matching path in the destination is the
      // project's own.
      if ($path === self::HARNESS_DIR || str_starts_with($path, self::HARNESS_DIR . '/')) {
        continue;
      }

      $target = $destination . '/' . $path;

      if (File::exists($target)) {
        File::remove($target);
      }

      for ($dir = dirname($path); $dir !== '.'; $dir = dirname($dir)) {
        $dirs[$dir] = substr_count($dir, '/');
      }
    }

    // Deepest first, so a parent is only tested once its children are gone.
    arsort($dirs);

    foreach (array_keys($dirs) as $dir) {
      File::rmdirIfEmpty($destination . '/' . $dir);
    }
  }

  /**
   * List the files within a directory, relative to it.
   *
   * @param string $directory
   *   Directory to scan.
   *
   * @return array<string>
   *   Relative file paths.
   */
  protected function relativePaths(string $directory): array {
    if (!is_dir($directory)) {
      return [];
    }

    $root = File::dir($directory);
    $files = File::scandir($root, File::ignoredPaths());

    return array_map(fn(string $file): string => ltrim(str_replace($root, '', $file), DIRECTORY_SEPARATOR), $files);
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
