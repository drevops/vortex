<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use CzProject\GitPhp\Git as GitWrapper;
use CzProject\GitPhp\GitRepository;
use CzProject\GitPhp\RunnerResult;

class Git extends GitRepository {

  /**
   * Initialize a new Git repository.
   *
   * @param string $path
   *   The path to the repository.
   *
   * @return \CzProject\GitPhp\GitRepository
   *   The Git repository.
   */
  public static function init(string $path): GitRepository {
    return (new GitWrapper())->init($path);
  }

  /**
   * {@inheritdoc}
   */
  public function run(...$args): RunnerResult {
    $command = array_shift($args);
    array_unshift($args, '--no-pager', $command);

    return parent::run(...$args);
  }

  /**
   * List remotes.
   *
   * @return array<string>
   *   Remotes.
   */
  public function listRemotes(): array {
    $remotes = [];

    $list = $this->extractFromCommand(['remote', '-v']) ?: [];
    foreach ($list as $line) {
      $parts = explode("\t", $line);
      if (count($parts) < 2) {
        continue;
      }
      // Remove the trailing (fetch) or (push) from the remote name.
      $parts[1] = preg_replace('/ \(.*\)$/', '', $parts[1]);
      $remotes[$parts[0]] = $parts[1];
    }

    return $remotes;
  }

  /**
   * Extract the owner/repo from a Git URI.
   *
   * @param string $uri
   *   The Git URI.
   *
   * @return string|null
   *   The owner/repo or NULL if not found.
   */
  public static function extractOwnerRepo(string $uri): ?string {
    if (preg_match('#^(?:git@|https://|git://|ssh://git@)([^/:]+)[:/]([^/]+)/([^/]+?)(?:\.git)?$#', $uri, $matches)) {
      return $matches[2] . '/' . $matches[3];
    }

    return NULL;
  }

  /**
   * Get the tracked files in a Git repository.
   *
   * @param string $dir
   *   The directory to check.
   *
   * @return array<string>
   *   The list of tracked files.
   *
   * @todo Refactor to use GitPhp.
   */
  public static function getTrackedFiles(string $dir): array {
    if (!is_dir($dir . '/.git')) {
      throw new \RuntimeException("The directory is not a Git repository.");
    }

    $tracked_files = [];
    $output = [];
    $code = 0;
    $command = sprintf("cd %s && git ls-files", escapeshellarg($dir));
    exec($command, $output, $code);
    if ($code !== 0) {
      throw new \RuntimeException("Failed to retrieve tracked files using git ls-files.");
    }

    foreach ($output as $file) {
      $tracked_files[] = $dir . DIRECTORY_SEPARATOR . $file;
    }

    return $tracked_files;
  }

  public function getLastShortCommitId(): string {
    $commit_id = self::getLastCommitId();
    return substr($commit_id->toString(), 0, 7);
  }

}
