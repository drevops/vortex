<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Utils;

use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Unified diffs of file content.
 */
class Diff {

  /**
   * Largest content, in bytes, that is rendered as a diff.
   */
  const MAX_BYTES = 102400;

  const SKIP_BINARY = 'binary';

  const SKIP_TOO_LARGE = 'too large';

  /**
   * Render a unified diff between 2 versions of content.
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
   *   The diff with its header, without a trailing newline.
   */
  public static function unified(string $from, string $to, string $from_label, string $to_label): string {
    $header = sprintf('--- %s', $from_label) . PHP_EOL . sprintf('+++ %s', $to_label) . PHP_EOL;
    $differ = new Differ(new UnifiedDiffOutputBuilder($header));

    return rtrim($differ->diff($from, $to));
  }

  /**
   * Find why content cannot be rendered as a diff.
   *
   * @param string ...$sides
   *   Every version of the content.
   *
   * @return string|null
   *   One of the SKIP_* constants, or NULL when the content can be diffed.
   */
  public static function skipReason(string ...$sides): ?string {
    foreach ($sides as $side) {
      if (str_contains($side, "\0")) {
        return self::SKIP_BINARY;
      }

      if (strlen($side) > self::MAX_BYTES) {
        return self::SKIP_TOO_LARGE;
      }
    }

    return NULL;
  }

}
