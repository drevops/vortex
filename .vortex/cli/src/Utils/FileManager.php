<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Utils;

use DrevOps\VortexCli\Downloader\Artifact;
use DrevOps\VortexCli\Downloader\Downloader;
use DrevOps\VortexCli\Downloader\RepositoryDownloader;

/**
 * File operations for the installation process.
 *
 * @package DrevOps\VortexCli\Utils
 */
class FileManager {

  /**
   * Path of the template's own harness, which is never shipped.
   */
  const HARNESS_DIR = '.vortex';

  /**
   * Algorithm used to detect project edits to shipped files.
   */
  const HASH_ALGO = 'sha256';

  /**
   * Paths shipped by the downloaded template, relative to its root.
   *
   * @var array<string>
   */
  protected array $templatePaths = [];

  /**
   * Content hashes the version the project currently runs installed.
   *
   * @var array<string, string>
   */
  protected array $previousTemplateHashes = [];

  /**
   * Directory holding the rendered version the project currently runs.
   */
  protected ?string $previousDir = NULL;

  /**
   * Reference of the version the project currently runs.
   */
  protected ?string $previousRef = NULL;

  /**
   * Path of the registry of project changes this update replaced.
   */
  protected ?string $registryFile = NULL;

  public function __construct(
    protected Config $config,
  ) {}

  /**
   * Empty the staging directory the template is downloaded into.
   */
  public function resetStaging(): void {
    $dir = $this->config->get(Config::TMP);

    File::remove($dir);
    File::mkdir($dir);
  }

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
   * Record what the version the project currently runs installed.
   *
   * A path the template has stopped shipping altogether is absent from the
   * incoming download, so the selection diff alone cannot identify it.
   * Rendering the version the project runs restores such a path as a
   * candidate, so a file dropped between releases is removable rather than
   * permanent.
   *
   * The download is rendered before hashing: token replacements and directory
   * renames leave the raw template files matching nothing in the project. It
   * is rendered as the destination has it installed, not as this run would
   * install it, so a path this run drops stays recognisable as template-owned.
   *
   * Failure is not fatal: the recorded reference may no longer resolve, in
   * which case only the selection diff applies.
   *
   * @param \DrevOps\VortexCli\Downloader\RepositoryDownloader $downloader
   *   The repository downloader.
   * @param \DrevOps\VortexCli\Downloader\Artifact $artifact
   *   The artifact identifying the repository to read the reference from.
   * @param callable|null $render
   *   Callback turning the download into installable content, receiving the
   *   directory and the reference.
   */
  public function snapshotPreviousTemplate(RepositoryDownloader $downloader, Artifact $artifact, ?callable $render = NULL): void {
    if (!$this->config->isVortexProject()) {
      return;
    }

    $ref = Version::detectProjectRef((string) $this->config->getDst());

    if ($ref === NULL) {
      return;
    }

    $dir = $this->config->get(Config::TMP) . '-previous';

    try {
      // The extraction unpacks into an existing directory.
      File::remove($dir);
      File::mkdir($dir);
      $downloader->download(Artifact::create($artifact->getRepo(), $ref), $dir);

      if ($render !== NULL) {
        $render($dir, $ref);
      }

      $this->previousTemplateHashes = $this->hashDirectory($dir);
      $this->previousDir = $dir;
      $this->previousRef = $ref;
    }
    catch (\Exception) {
      $this->previousTemplateHashes = [];
      $this->previousDir = NULL;
      File::remove($dir);
    }
  }

  /**
   * Prepare the destination directory.
   *
   * @return array<string>
   *   Array of status messages.
   */
  public function prepareDestination(): array {
    $messages = [];

    $dst = $this->config->getDst();
    if (!is_dir($dst)) {
      $dst = File::mkdir($dst);
      $messages[] = sprintf('Created directory "%s".', $dst);
    }

    if (!is_readable($dst . '/.git')) {
      $messages[] = sprintf('Initialising a new Git repository in directory "%s".', $dst);
      // 'git init' writes the initial branch advice to stderr when
      // 'init.defaultBranch' is unset, and passthru() draws it inside the TUI.
      passthru(sprintf('git -c advice.defaultBranchName=false --work-tree="%s" --git-dir="%s/.git" init > /dev/null', $dst, $dst));

      if (!File::exists($dst . '/.git')) {
        throw new \RuntimeException(sprintf('Unable to initialise Git repository in directory "%s".', $dst));
      }
    }

    return $messages;
  }

  public function copyFiles(): void {
    $src = $this->config->get(Config::TMP);
    $dst = $this->config->getDst();

    // What the project should hold for a path this install no longer ships.
    $expected = $this->previousTemplateHashes;

    // Anything the version the project runs installed, or either version of
    // the template ships, but the staged copy no longer holds.
    $shipped = array_merge(array_keys($expected), $this->templatePaths);
    $excluded = array_diff($shipped, $this->relativePaths($src));

    // Due to the way symlinks can be ordered, we cannot copy files one-by-one
    // into destination directory. Instead, we are removing all ignored files
    // and empty directories, making the src directory "clean", and then
    // recursively copying the whole directory.
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

    // Remove skipped files.
    foreach ($ignored_files as $ignored_file) {
      if (is_readable($ignored_file)) {
        File::remove($ignored_file);
      }
    }

    // Remove empty directories.
    foreach ($dirs as $dir) {
      File::rmdirIfEmpty($dir);
    }

    $this->recordReplacedChanges($src);

    // Src directory is now "clean" - copy it to dst directory.
    if (is_dir($src) && !File::dirIsEmpty($src)) {
      File::copy($src, $dst);
    }

    // Special case for .env.local as it may exist.
    if (!file_exists($dst . '/.env.local') && file_exists($dst . '/.env.local.example')) {
      File::copy($dst . '/.env.local.example', $dst . '/.env.local');
    }

    $this->removeExcludedPaths($excluded, $expected);
    $this->removeObsoletePaths();
    $this->cleanupPreviousTemplate();
  }

  /**
   * Record the project changes that the copy is about to replace.
   *
   * The copy overlays the staged content without regard for what the project
   * put there, so a change the project made to a shipped file is lost. The
   * content of all three sides is only available before the overlay, so the
   * registry is built first.
   *
   * @param string $src
   *   The staged template directory.
   */
  protected function recordReplacedChanges(string $src): void {
    if ($this->previousDir === NULL) {
      return;
    }

    $dst = (string) $this->config->getDst();
    $registry = new UpdateRegistry($dst);

    foreach ($this->relativePaths($src) as $path) {
      $project = $dst . '/' . $path;

      if (!is_file($project) || !is_readable($project)) {
        continue;
      }

      $next = $src . '/' . $path;
      $project_hash = hash_file(self::HASH_ALGO, $project);

      // The file still holds what the template put there, or already holds
      // what the copy would put there, so the copy replaces nothing.
      if ($project_hash === ($this->previousTemplateHashes[$path] ?? NULL) || $project_hash === hash_file(self::HASH_ALGO, $next)) {
        continue;
      }

      // A path the version the project runs never shipped has no content to
      // diff the project's copy against.
      $previous = $this->previousDir . '/' . $path;

      $registry->add($path, is_file($previous) ? File::read($previous) : NULL, File::read($project), File::read($next));
    }

    $this->registryFile = $registry->write((string) $this->previousRef, (string) $this->config->get(Config::VERSION), date('Y-m-d H:i:s'));
  }

  /**
   * Remove the rendered copy of the version the project currently runs.
   */
  protected function cleanupPreviousTemplate(): void {
    if ($this->previousDir !== NULL) {
      File::remove($this->previousDir);
      $this->previousDir = NULL;
    }
  }

  /**
   * Get the registry of project changes this update replaced.
   *
   * @return string|null
   *   Path of the registry, or NULL when no project change was replaced.
   */
  public function getRegistryFile(): ?string {
    return $this->registryFile;
  }

  /**
   * Remove paths the install no longer ships from the destination.
   *
   * The staged copy is overlaid onto the destination without a delete pass, so
   * a path this install drops would otherwise survive and keep being detected
   * as an active feature. A path is only removed when the project's copy still
   * matches what the template put there: a project that edited the file owns
   * it, and an edit that cannot be ruled out is treated as one. Only projects
   * already running Vortex are pruned.
   *
   * @param array<string> $paths
   *   Template-relative paths absent from the staged copy.
   * @param array<string, string> $expected
   *   Content hashes the template last installed, keyed by path.
   */
  protected function removeExcludedPaths(array $paths, array $expected): void {
    if (!$this->config->isVortexProject()) {
      return;
    }

    $dst = $this->config->getDst();
    $dirs = [];

    foreach ($paths as $path) {
      // The harness never ships, so a matching path in the destination is the
      // project's own.
      if ($path === self::HARNESS_DIR || str_starts_with($path, self::HARNESS_DIR . '/')) {
        continue;
      }

      $target = $dst . '/' . $path;

      if (!is_file($target)) {
        continue;
      }

      // Without a recorded hash there is nothing to compare the project's copy
      // against, so ownership cannot be established.
      if (!isset($expected[$path]) || hash_file(self::HASH_ALGO, $target) !== $expected[$path]) {
        continue;
      }

      File::remove($target);

      for ($dir = dirname($path); $dir !== '.'; $dir = dirname($dir)) {
        $dirs[$dir] = substr_count($dir, '/');
      }
    }

    // Deepest first, so a parent is only tested once its children are gone.
    arsort($dirs);

    foreach (array_keys($dirs) as $dir) {
      File::rmdirIfEmpty($dst . '/' . $dir);
    }
  }

  /**
   * Hash every file within a directory, keyed by its relative path.
   *
   * @param string $directory
   *   Directory to scan.
   *
   * @return array<string, string>
   *   Content hashes keyed by relative path.
   */
  protected function hashDirectory(string $directory): array {
    $hashes = [];

    foreach ($this->relativePaths($directory) as $path) {
      $file = $directory . '/' . $path;

      if (is_file($file)) {
        $hashes[$path] = (string) hash_file(self::HASH_ALGO, $file);
      }
    }

    return $hashes;
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
   * the current version no longer ships.
   */
  public function removeObsoletePaths(): void {
    $dst = $this->config->getDst();

    // 'scripts/vortex/' was the location of shipped Vortex scripts before
    // they were extracted into the 'drevops/vortex-tooling' Composer package.
    // Consumer projects updated from older Vortex versions still have it.
    $obsolete = [
      'scripts/vortex',
    ];

    foreach ($obsolete as $relative) {
      $path = $dst . DIRECTORY_SEPARATOR . $relative;
      if (file_exists($path)) {
        File::remove($path);
      }
    }
  }

  /**
   * Prepare demo content if in demo mode.
   *
   * @param \DrevOps\VortexCli\Downloader\Downloader $downloader
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
    Env::putFromDotenv($this->config->getDst() . '/.env');

    $url = Env::get('VORTEX_FETCH_DB_URL');
    if (empty($url)) {
      return 'No database fetch URL provided. Skipping demo database fetch.';
    }

    $data_dir = $this->config->getDst() . DIRECTORY_SEPARATOR . Env::get('VORTEX_DB_DIR', './.data');
    $db_file = Env::get('VORTEX_DB_FILE', 'db.sql');

    if (file_exists($data_dir . DIRECTORY_SEPARATOR . $db_file)) {
      return 'Database dump file already exists. Skipping demo database fetch.';
    }

    $messages = [];
    if (!file_exists($data_dir)) {
      $data_dir = File::mkdir($data_dir);
      $messages[] = sprintf('Created data directory "%s".', $data_dir);
    }

    $destination = $data_dir . DIRECTORY_SEPARATOR . $db_file;
    $downloader->download($url, $destination);

    $messages[] = sprintf('No database dump file was found in "%s" directory.', $data_dir);
    $messages[] = sprintf('Fetched demo database from %s.', $url);

    return $messages;
  }

}
