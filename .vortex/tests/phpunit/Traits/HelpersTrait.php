<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

use AlexSkrypnyk\File\File;
use AlexSkrypnyk\File\Tests\Traits\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Tests\Traits\FileAssertionsTrait;

trait HelpersTrait {

  use DirectoryAssertionsTrait;
  use FileAssertionsTrait;

  protected function volumesMounted(): bool {
    return getenv('VORTEX_DEV_VOLUMES_SKIP_MOUNT') != 1;
  }

  protected function forceVolumesUnmounted(): void {
    putenv('VORTEX_DEV_VOLUMES_SKIP_MOUNT=1');
  }

  protected function syncToHost(string|array $paths = []): void {
    if ($this->volumesMounted()) {
      return;
    }

    $paths = array_filter(is_array($paths) ? $paths : [$paths]);
    if (empty($paths)) {
      $this->logNote('Syncing all files from container to host');
      shell_exec('docker compose cp -L cli:/app/. . > /dev/null 2>&1');
      return;
    }

    foreach ($paths as $path) {
      $path = (string) $path;
      $rel_path = ltrim($path, '/');
      $container_abs = '/app/' . $rel_path;

      // Probe container to check if directory, file, or missing.
      $path_type_cmd = sprintf(
        'docker compose exec -T cli bash -lc %s',
        escapeshellarg(
          sprintf('[ -d %s ] && echo DIR || { [ -f %s ] && echo FILE || echo MISSING; }',
            escapeshellarg($container_abs),
            escapeshellarg($container_abs)
          )
        )
      );

      $path_type = trim((string) shell_exec($path_type_cmd));

      if ($path_type === 'DIR') {
        $host_dir = $rel_path === '' ? '.' : $rel_path;
        shell_exec(sprintf('mkdir -p %s > /dev/null 2>&1', escapeshellarg($host_dir)));

        $this->logNote('Syncing directory (contents) from container to host: ' . $path);
        shell_exec(sprintf('docker compose cp -L cli:%s/. %s > /dev/null 2>&1', escapeshellarg($container_abs), escapeshellarg($host_dir)));
      }
      elseif ($path_type === 'FILE') {
        $dst_dir = dirname($rel_path);
        if ($dst_dir === '.' || $dst_dir === '') {
          $dst_dir = '.';
        }
        else {
          shell_exec(sprintf('mkdir -p %s > /dev/null 2>&1', escapeshellarg($dst_dir)));
        }

        $this->logNote(sprintf('Syncing file from container to host: %s', $path));
        $cp_cmd = sprintf('docker compose cp -L cli:%s %s > /dev/null 2>&1', escapeshellarg($container_abs), escapeshellarg($dst_dir));
        shell_exec($cp_cmd);
      }
      else {
        throw new \InvalidArgumentException('Unable to sync path - file or directory does not exist in container: ' . $path);
      }
    }
  }

  protected function syncToContainer(string|array $paths = []): void {
    if ($this->volumesMounted()) {
      return;
    }

    $paths = array_filter(is_array($paths) ? $paths : [$paths]);
    if (empty($paths)) {
      $this->logNote('Syncing all files from host to container');
      shell_exec('docker compose cp -L . cli:/app/ > /dev/null 2>&1');
      return;
    }

    foreach ($paths as $path) {
      if (!File::exists($path)) {
        throw new \InvalidArgumentException('Unable to sync path - file or directory does not exist: ' . $path);
      }

      if (is_dir($path)) {
        $this->logNote('Syncing directory contents from host to container: ' . $path);
        $cmd = sprintf('docker compose cp -L %s/. cli:/app/%s > /dev/null 2>&1', escapeshellarg($path), escapeshellarg($path));
        shell_exec($cmd);
      }
      else {
        $this->logNote('Syncing file from host to container: ' . $path);
        $cmd = sprintf('docker compose exec -T cli bash -lc %s > /dev/null 2>&1',
          escapeshellarg(sprintf('mkdir -p %s', escapeshellarg('/app/' . dirname($path))))
        );
        shell_exec($cmd);

        $cmd = sprintf('docker compose cp -L %s cli:/app/%s > /dev/null 2>&1', escapeshellarg($path), escapeshellarg($path));
        shell_exec($cmd);
      }
    }
  }

  protected function removePathHostAndContainer(string $path): void {
    File::remove($path);
    shell_exec(sprintf('docker compose exec -T cli rm -rf %s', escapeshellarg('/app/' . ltrim($path, '/'))));
  }

  protected function fileBackup(string $file): void {
    File::copy($file, static::$tmp . '/bkp/' . basename($file), 0755);
  }

  protected function fileRestore(string $file): void {
    $backup_file = static::$tmp . '/bkp/' . basename($file);
    if (!File::exists($backup_file)) {
      throw new \InvalidArgumentException('No backup file exists for: ' . $file);
    }
    File::copy($backup_file, $file);
  }

  protected function fileAppend(string $path, string $content): void {
    $this->fileBackup($path);
    File::append($path, $content);
  }

  protected function fileAddVar(string $file, string $var, string|int|float $value): void {
    $this->fileBackup($file);
    File::append($file, sprintf(PHP_EOL . '%s=%s' . PHP_EOL, $var, strval($value)));
  }

  protected function fetchWebpageContent(string $path): string {
    $this->cmd('docker compose exec -T cli curl -L -s ' . escapeshellarg('http://nginx:8080' . $path), txt: 'Fetch webpage content');

    $content = $this->processGet()->getOutput();

    $filename = static::$tmp . '/fetch_webpage_content/' . md5($path) . '.html';
    File::dump($filename, $content);
    $this->logNote('Webpage content saved to: ' . $filename);

    return $content;
  }

}
