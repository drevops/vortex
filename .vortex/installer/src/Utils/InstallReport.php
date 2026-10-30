<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

/**
 * Report of what an installation did to each template file.
 *
 * Every path the installation wrote, left alone or removed is listed with a
 * status, so a reviewer can tell the files applied as-is from the files that
 * replaced project changes. Each changed file links to a diff of the change,
 * written to a directory named after the report.
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

  /**
   * Extension of the report file.
   */
  const EXTENSION = '.md';

  /**
   * Extension of each diff file, appended to the file's path.
   */
  const DIFF_EXTENSION = '.diff';

  const STATUS_ADDED = 'added';

  const STATUS_UPDATED = 'updated';

  const STATUS_UNCHANGED = 'unchanged';

  const STATUS_OVERWRITTEN = 'overwritten';

  const STATUS_REMOVED = 'removed';

  const STATUS_KEPT = 'kept';

  const STATUS_FAILED = 'failed';

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
    self::STATUS_FAILED => 'The installer could not write the template copy, so the project file was left as it was.',
  ];

  /**
   * Statuses keyed by template-relative path.
   *
   * @var array<string, string>
   */
  protected array $entries = [];

  /**
   * Content before and after the installation, keyed by template-relative path.
   *
   * @var array<string, array{from: string, to: string|null}>
   */
  protected array $changes = [];

  public function __construct(
    protected string $destination,
  ) {}

  /**
   * Record the status of a path.
   *
   * A new status discards any change recorded for the path, since the change
   * belongs to the status it was recorded with.
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
    unset($this->changes[$path]);
  }

  /**
   * Record the change the installation made to a path's content.
   *
   * @param string $path
   *   Template-relative path, already recorded with a status.
   * @param string $from
   *   Content the project held before the installation.
   * @param string|null $to
   *   Content the installation left, or NULL when it removed the file.
   */
  public function addChange(string $path, string $from, ?string $to): void {
    if (!isset($this->entries[$path])) {
      throw new \InvalidArgumentException(sprintf('No status is recorded for "%s".', $path));
    }

    $this->changes[$path] = ['from' => $from, 'to' => $to];
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
   * Write the report and a diff file for each recorded change.
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
    $name = $base;

    // Runs within the same second share a timestamp.
    for ($suffix = 1; File::exists($name . self::EXTENSION) || File::exists($name); $suffix++) {
      $name = sprintf('%s-%d', $base, $suffix);
    }

    $links = $this->writeDiffs($name);
    $file = $name . self::EXTENSION;

    File::dump($file, $this->render($from, $to, $timestamp, $links));

    return $file;
  }

  /**
   * Write a diff file for each recorded change.
   *
   * @param string $dir
   *   Directory to write the diffs into.
   *
   * @return array<string, string>
   *   Link to each written diff relative to the report, or the reason a change
   *   was not diffed, keyed by template-relative path.
   */
  protected function writeDiffs(string $dir): array {
    $links = [];

    foreach ($this->changes as $path => $change) {
      $skip = Diff::skipReason($change['from'], (string) $change['to']);

      if ($skip !== NULL) {
        $links[$path] = $skip;
        continue;
      }

      $to_label = $change['to'] === NULL ? '/dev/null' : 'b/' . $path;
      $diff = Diff::unified($change['from'], (string) $change['to'], 'a/' . $path, $to_label);

      File::dump($dir . '/' . $path . self::DIFF_EXTENSION, $diff . PHP_EOL);

      $links[$path] = sprintf('[diff](%s)', $this->encodeLink(basename($dir) . '/' . $path . self::DIFF_EXTENSION));
    }

    return $links;
  }

  /**
   * Render the report as Markdown.
   *
   * @param string|null $from
   *   Version the project ran, or NULL when unknown.
   * @param string $to
   *   Version the installation installed.
   * @param int $timestamp
   *   Time of the installation.
   * @param array<string, string> $links
   *   Link to each path's diff, or the reason it has none.
   *
   * @return string
   *   The rendered report.
   */
  protected function render(?string $from, string $to, int $timestamp, array $links): string {
    // The report sits in DIR, so project paths are reached from its parent.
    $root = str_repeat('../', substr_count(self::DIR, '/') + 1);

    $content = '# Vortex installation report' . PHP_EOL . PHP_EOL;

    if ($from !== NULL && $from !== '') {
      $content .= sprintf('- **From:** %s', $from) . PHP_EOL;
    }

    $content .= sprintf('- **To:** %s', $to) . PHP_EOL;
    $content .= sprintf('- **Date:** %s', date('Y-m-d H:i:s', $timestamp)) . PHP_EOL . PHP_EOL;

    $content .= '## Statuses' . PHP_EOL . PHP_EOL;
    $content .= '| Status | Meaning |' . PHP_EOL . '|---|---|' . PHP_EOL;
    foreach (self::STATUSES as $status => $description) {
      $content .= sprintf('| `%s` | %s |', $status, $description) . PHP_EOL;
    }

    $content .= PHP_EOL . '## Summary' . PHP_EOL . PHP_EOL;
    $content .= '| Status | Files |' . PHP_EOL . '|---|---|' . PHP_EOL;
    foreach ($this->summary() as $status => $count) {
      $content .= sprintf('| `%s` | %d |', $status, $count) . PHP_EOL;
    }

    $content .= PHP_EOL . '## Files' . PHP_EOL . PHP_EOL;
    $content .= 'Each diff shows the project copy before the installation as `a/` and the copy it left as `b/`.' . PHP_EOL . PHP_EOL;
    $content .= '| Status | File | Change |' . PHP_EOL . '|---|---|---|' . PHP_EOL;
    foreach ($this->getEntries() as $path => $status) {
      // A removed path has no file left to link to.
      $file = File::exists($this->destination . '/' . $path) ? sprintf('[%s](%s)', $path, $this->encodeLink($root . $path)) : sprintf('`%s`', $path);

      $content .= sprintf('| `%s` | %s | %s |', $status, $file, $links[$path] ?? '') . PHP_EOL;
    }

    return $content;
  }

  /**
   * Encode a relative path for use as a Markdown link target.
   */
  protected function encodeLink(string $path): string {
    return implode('/', array_map(rawurlencode(...), explode('/', $path)));
  }

}
