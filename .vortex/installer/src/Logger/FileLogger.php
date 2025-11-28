<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Logger;

use DrevOps\VortexInstaller\Utils\File;

/**
 * File-based logger for command execution.
 */
class FileLogger implements FileLoggerInterface {

  /**
   * Default log directory name.
   */
  const string LOG_DIR = '.logs';

  /**
   * Whether logging is enabled.
   */
  protected bool $enabled = TRUE;

  /**
   * The base directory for log files.
   */
  protected string $dir = '';

  /**
   * The current log file path.
   */
  protected ?string $path = NULL;

  /**
   * The file handle.
   *
   * @var resource|null
   */
  protected mixed $handle = NULL;

  /**
   * {@inheritdoc}
   */
  public function open(string $command, array $args = []): bool {
    if (!$this->enabled) {
      return FALSE;
    }

    $name = $this->buildFilename($command, $args);
    $this->path = $this->getDir() . '/' . static::LOG_DIR . '/' . $name . '-' . date('Y-m-d-His') . '.log';

    $log_dir = dirname($this->path);
    if (!is_dir($log_dir)) {
      File::mkdir($log_dir);
    }

    // @phpstan-ignore-next-line
    $this->handle = fopen($this->path, 'w');

    return $this->handle !== FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function write(string $content): void {
    if ($this->handle !== NULL) {
      fwrite($this->handle, $content);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function close(): void {
    if ($this->handle !== NULL) {
      fclose($this->handle);
      $this->handle = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPath(): ?string {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(): static {
    $this->enabled = TRUE;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable(): static {
    $this->enabled = FALSE;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDir(string $dir): static {
    $this->dir = $dir;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDir(): string {
    if ($this->dir === '') {
      $this->dir = (string) getcwd();
    }

    return $this->dir;
  }

  /**
   * Build log filename from command and arguments.
   *
   * @param string $command
   *   The base command.
   * @param array<int, string> $args
   *   Command arguments (not options).
   *
   * @return string
   *   Sanitized filename suitable for log file.
   */
  protected function buildFilename(string $command, array $args = []): string {
    $parts = [$command];

    // Only include positional arguments, not options (starting with -).
    foreach ($args as $arg) {
      if (!str_starts_with($arg, '-')) {
        $parts[] = $arg;
      }
    }

    // Sanitize for use in filename.
    $name = implode('-', $parts);
    $name = (string) preg_replace('/[^a-zA-Z0-9\-_]/', '-', $name);
    $name = (string) preg_replace('/-+/', '-', $name);
    $name = trim($name, '-');

    return $name !== '' ? $name : 'runner';
  }

}
