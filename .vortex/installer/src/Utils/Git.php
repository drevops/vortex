<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use CzProject\GitPhp\GitRepository;
use CzProject\GitPhp\RunnerResult;

class Git extends GitRepository {

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

  public static function extractOwnerRepo(string $uri): ?string {
    if (preg_match('#^(?:git@|https://|git://|ssh://git@)([^/:]+)[:/]([^/]+)/([^/]+?)(?:\.git)?$#', $uri, $matches)) {
      return $matches[2] . '/' . $matches[3];
    }

    return NULL;
  }

  public static function init(string $path): GitRepository {
    return (new \CzProject\GitPhp\Git())->init($path);
  }

}
