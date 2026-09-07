<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Record of project changes that an update replaced.
 *
 * An update overwrites the project's copy of every path the template still
 * ships. Each entry holds the change that overwrite replaced alongside the
 * change the update brings.
 */
class UpdateRegistry {

  /**
   * Path of the registry within the project, relative to its root.
   */
  const FILE = '.logs/vortex-update.md';

  /**
   * Heading written once, above every recorded update.
   */
  const HEADING = <<<'MD'
    # Vortex update registry

    Each section below lists the files whose project content a Vortex update replaced. For every file, the diffs show the change the project had made and the change the update brings. Re-apply the project change onto the updated file, then delete the entry.

    MD;

  /**
   * Largest content, in bytes, that is rendered as a diff.
   */
  const MAX_DIFF_BYTES = 102400;

  /**
   * Replaced content, keyed by template-relative path.
   *
   * @var array<string, array{previous: string, project: string, next: string}>
   */
  protected array $entries = [];

  public function __construct(
    protected string $destination,
  ) {}

  /**
   * Record a path the update is about to replace.
   *
   * @param string $path
   *   Template-relative path.
   * @param string $previous
   *   Content the version the project runs installed, empty when it shipped
   *   no such path.
   * @param string $project
   *   Content the project holds.
   * @param string $next
   *   Content the update installs.
   */
  public function add(string $path, string $previous, string $project, string $next): void {
    $this->entries[$path] = ['previous' => $previous, 'project' => $project, 'next' => $next];
  }

  /**
   * Check whether anything was recorded.
   */
  public function isEmpty(): bool {
    return $this->entries === [];
  }

  /**
   * Append the recorded entries to the registry.
   *
   * @param string $from
   *   Version the project runs.
   * @param string $to
   *   Version the update installs.
   * @param string $time
   *   Timestamp of the update.
   *
   * @return string|null
   *   Absolute path of the registry, or NULL when nothing was recorded.
   */
  public function write(string $from, string $to, string $time): ?string {
    if ($this->isEmpty()) {
      return NULL;
    }

    ksort($this->entries);

    $content = sprintf('## %s to %s, %s', $from, $to, $time) . PHP_EOL . PHP_EOL;

    foreach ($this->entries as $path => $contents) {
      $content .= $this->renderEntry($path, $contents);
    }

    $file = $this->destination . '/' . self::FILE;
    $existing = is_file($file) ? File::read($file) : self::HEADING . PHP_EOL;

    File::dump($file, $existing . $content);

    return $file;
  }

  /**
   * Render a single entry as Markdown.
   *
   * @param string $path
   *   Template-relative path.
   * @param array{previous: string, project: string, next: string} $contents
   *   The three versions of the file's content.
   *
   * @return string
   *   The rendered entry.
   */
  protected function renderEntry(string $path, array $contents): string {
    $content = sprintf('### %s', $path) . PHP_EOL . PHP_EOL;

    foreach ($contents as $side) {
      if (str_contains($side, "\0")) {
        return $content . 'Binary file. Recover the project copy from version control.' . PHP_EOL . PHP_EOL;
      }

      if (strlen($side) > self::MAX_DIFF_BYTES) {
        return $content . 'File is too large to diff. Recover the project copy from version control.' . PHP_EOL . PHP_EOL;
      }
    }

    if ($contents['previous'] === '') {
      $content .= 'The version the project runs did not ship this file. Project content that the update replaced:' . PHP_EOL . PHP_EOL;

      return $content . $this->renderDiff($contents['project'], $contents['next'], 'project', 'update');
    }

    $content .= 'Project change that the update replaced:' . PHP_EOL . PHP_EOL;
    $content .= $this->renderDiff($contents['previous'], $contents['project'], 'installed', 'project');

    if ($contents['previous'] === $contents['next']) {
      return $content . 'The update ships this file unchanged.' . PHP_EOL . PHP_EOL;
    }

    $content .= 'Change that the update brings:' . PHP_EOL . PHP_EOL;

    return $content . $this->renderDiff($contents['previous'], $contents['next'], 'installed', 'update');
  }

  /**
   * Render a unified diff inside a fenced code block.
   *
   * @param string $from
   *   Content to diff from.
   * @param string $to
   *   Content to diff to.
   * @param string $from_label
   *   Label for the left side.
   * @param string $to_label
   *   Label for the right side.
   *
   * @return string
   *   The fenced diff.
   */
  protected function renderDiff(string $from, string $to, string $from_label, string $to_label): string {
    $header = sprintf('--- %s', $from_label) . PHP_EOL . sprintf('+++ %s', $to_label) . PHP_EOL;
    $differ = new Differ(new UnifiedDiffOutputBuilder($header));

    return '```diff' . PHP_EOL . rtrim($differ->diff($from, $to)) . PHP_EOL . '```' . PHP_EOL . PHP_EOL;
  }

}
