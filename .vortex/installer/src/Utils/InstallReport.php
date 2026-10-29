<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

/**
 * Report of what an installation did to each template file.
 *
 * Every path the installation wrote, left alone or removed is listed with a
 * status, so a reviewer can tell the files applied as-is from the files that
 * replaced project changes.
 */
class InstallReport {

  /**
   * Directory of the report within the project, relative to its root.
   */
  const DIR = '.logs';

  /**
   * Prefix of the report file name, followed by the timestamp.
   */
  const PREFIX = 'vortex-install-';

  const STATUS_ADDED = 'added';

  const STATUS_UPDATED = 'updated';

  const STATUS_UNCHANGED = 'unchanged';

  const STATUS_OVERWRITTEN = 'overwritten';

  const STATUS_REMOVED = 'removed';

  const STATUS_KEPT = 'kept';

  /**
   * Descriptions of the statuses, in the order they are rendered.
   */
  const STATUSES = [
    self::STATUS_ADDED => 'The project did not have the file, so the template copy was added.',
    self::STATUS_UPDATED => 'The project had not changed the file, so the template copy replaced it as-is.',
    self::STATUS_UNCHANGED => 'The project file already matched the template copy.',
    self::STATUS_OVERWRITTEN => 'The project file held its own changes or could not be read, and the template copy replaced it.',
    self::STATUS_REMOVED => 'The template no longer installs the file, so the project copy was removed.',
    self::STATUS_KEPT => 'The template no longer installs the file, but the project had changed it, so it was kept.',
  ];

  /**
   * Statuses keyed by template-relative path.
   *
   * @var array<string, string>
   */
  protected array $entries = [];

  public function __construct(
    protected string $destination,
  ) {}

  /**
   * Record the status of a path.
   *
   * @param string $path
   *   Template-relative path.
   * @param string $status
   *   One of the STATUS_* constants.
   */
  public function add(string $path, string $status): void {
    if (!array_key_exists($status, self::STATUSES)) {
      throw new \InvalidArgumentException(sprintf('Unknown install report status "%s".', $status));
    }

    $this->entries[$path] = $status;
  }

  /**
   * Check whether anything was recorded.
   */
  public function isEmpty(): bool {
    return $this->entries === [];
  }

  /**
   * Get the recorded statuses, sorted by path.
   *
   * @return array<string, string>
   *   Statuses keyed by template-relative path.
   */
  public function getEntries(): array {
    $entries = $this->entries;
    ksort($entries);

    return $entries;
  }

  /**
   * Count the recorded paths per status.
   *
   * @return array<string, int>
   *   Counts keyed by status, in render order, without zero counts.
   */
  public function summary(): array {
    $counts = array_count_values($this->entries);

    return array_filter(array_merge(array_fill_keys(array_keys(self::STATUSES), 0), $counts));
  }

  /**
   * Write the report.
   *
   * @param string|null $from
   *   Version the project ran, or NULL when unknown.
   * @param string $to
   *   Version the installation installed.
   * @param int $timestamp
   *   Time of the installation.
   *
   * @return string|null
   *   Absolute path of the report, or NULL when nothing was recorded.
   */
  public function write(?string $from, string $to, int $timestamp): ?string {
    if ($this->isEmpty()) {
      return NULL;
    }

    $base = sprintf('%s/%s/%s%s', $this->destination, self::DIR, self::PREFIX, date('Y-m-d-His', $timestamp));
    $file = $base . '.txt';

    // Runs within the same second share a timestamp.
    for ($suffix = 1; File::exists($file); $suffix++) {
      $file = sprintf('%s-%d.txt', $base, $suffix);
    }

    File::dump($file, $this->render($from, $to, $timestamp));

    return $file;
  }

  /**
   * Render the report as plain text.
   *
   * @param string|null $from
   *   Version the project ran, or NULL when unknown.
   * @param string $to
   *   Version the installation installed.
   * @param int $timestamp
   *   Time of the installation.
   *
   * @return string
   *   The rendered report.
   */
  protected function render(?string $from, string $to, int $timestamp): string {
    $width = max(array_map(strlen(...), array_keys(self::STATUSES))) + 2;
    $line = fn(string $label, string|int $value): string => '  ' . str_pad($label, $width) . $value . PHP_EOL;

    $content = 'Vortex installation report' . PHP_EOL . PHP_EOL;

    if ($from !== NULL && $from !== '') {
      $content .= 'From: ' . $from . PHP_EOL;
    }

    $content .= 'To:   ' . $to . PHP_EOL;
    $content .= 'Date: ' . date('Y-m-d H:i:s', $timestamp) . PHP_EOL . PHP_EOL;

    $content .= 'Statuses:' . PHP_EOL;
    foreach (self::STATUSES as $status => $description) {
      $content .= $line($status, $description);
    }

    $content .= PHP_EOL . 'Summary:' . PHP_EOL;
    foreach ($this->summary() as $status => $count) {
      $content .= $line($status, $count);
    }

    $content .= PHP_EOL . 'Files:' . PHP_EOL;
    foreach ($this->getEntries() as $path => $status) {
      $content .= $line($status, $path);
    }

    return $content;
  }

}
