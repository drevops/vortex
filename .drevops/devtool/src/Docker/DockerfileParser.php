<?php

namespace DrevOps\DevTool\Docker;

/**
 * Class DockerfileParser.
 *
 * Lightweight Dockerfile parser .
 *
 * @package DrevOps\DevTool\Docker
 */
class DockerfileParser {

  /**
   * Parse a Dockerfile.
   *
   * @param string $path
   *   Path to the Dockerfile.
   *
   * @return array<DockerCommand>
   *   An array of DockerCommand objects.
   */
  public static function parse(string $path): array {
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $commands = [];
    $currentCommand = '';

    foreach ($lines as $line) {
      // Check for continuation lines.
      if (preg_match('/\\\\\s*$/', $line)) {
        $currentCommand .= preg_replace('/\\\\\s*$/', ' ', $line);
        continue;
      }

      $currentCommand .= $line;

      // Split the command into keyword and arguments.
      if (preg_match('/^(\w+)\s+(.*)$/', $currentCommand, $matches)) {
        try {
          $command = new DockerCommand($matches[1], $matches[2]);
        }
        catch (\Exception $e) {
          continue;
        }
        $commands[] = $command;
      }

      $currentCommand = '';
    }

    return $commands;
  }

}
