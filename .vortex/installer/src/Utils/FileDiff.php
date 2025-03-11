<?php

declare(strict_types=1);

namespace DrevOps\Installer\Utils;

use Jfcherng\Diff\DiffHelper;
use Jfcherng\Diff\Renderer\RendererConstant;

class FileDiff {

  /**
   * Assert that 2 directories have the same files and content, ignoring some.
   *
   * The main purpose of this method is to allow to create before/after file
   * structures and compare them, ignoring some files or content changes. This
   * allows to create fixture hierarchies fast.
   *
   * The first directory could be updated using the files from the second
   * directory if the environment variable `UPDATE_TEST_FIXTURES` is set.
   * This is useful to update the fixtures after the changes in the code.
   *
   * Files can be excluded from the comparison completely or only checked for
   * presence and ignored for the content changes using a .gitignore-like
   * file `.ignorecontent` that can be placed in the second directory.
   *
   * The syntax for the file is similar to .gitignore with addition of
   * the content ignoring using ^ prefix:
   * Comments start with #.
   * file    Ignore file.
   * dir/    Ignore directory and all subdirectories.
   * dir/*   Ignore all files in directory, but not subdirectories.
   * ^file   Ignore content changes in file, but not the file itself.
   * ^dir/   Ignore content changes in all files and subdirectories, but check
   *         that the directory itself exists.
   * ^dir/*  Ignore content changes in all files, but not subdirectories and
   *         check that the directory itself exists.
   * !file   Do not ignore file.
   * !dir/   Do not ignore directory, including all subdirectories.
   * !dir/*  Do not ignore all files in directory, but not subdirectories.
   * !^file  Do not ignore content changes in file.
   * !^dir/  Do not ignore content changes in all files and subdirectories.
   * !^dir/* Do not ignore content changes in all files, but not subdirectories.
   *
   * This assertion method is deliberately used as a single assertion for
   * portability.
   *
   * @param string $dir1
   *   The first directory.
   * @param string $dir2
   *   The second directory.
   * @param callable|null $before_match_content
   *   A callback to modify the content of the files before comparison.
   */
  public static function compare(string $dir1, string $dir2, ?callable $before_match_content = NULL): array {
    $rules = File::contentignore($dir1 . DIRECTORY_SEPARATOR . File::IGNORECONTENT);
    $rules[File::RULE_SKIP] = array_merge($rules[File::RULE_SKIP], [File::IGNORECONTENT, '.git/']);

    $dir1_files = File::list($dir1, $rules, $before_match_content);
    $dir2_files = File::list($dir2, $rules, $before_match_content);

    $diffs = [
      'dir1' => $dir1,
      'dir2' => $dir2,
      'absent_dir1' => array_diff_key($dir2_files, $dir1_files),
      'absent_dir2' => array_diff_key($dir1_files, $dir2_files),
      'content' => [],
    ];

    foreach ($dir1_files as $file => $hash) {
      if (isset($dir2_files[$file]) && $hash !== $dir2_files[$file] && !in_array($file, $rules[File::RULE_IGNORE_CONTENT])) {
        $diffs['content'][$file]['dir1'] = file_get_contents($dir1 . DIRECTORY_SEPARATOR . $file);
        $diffs['content'][$file]['dir2'] = '';
      }
    }

    foreach ($dir2_files as $file => $hash) {
      if (isset($dir1_files[$file]) && $hash !== $dir1_files[$file] && !in_array($file, $rules[File::RULE_IGNORE_CONTENT])) {
        $diffs['content'][$file]['dir1'] = $diffs['content'][$file]['dir1'] ?: '';
        $diffs['content'][$file]['dir2'] = file_get_contents($dir2 . DIRECTORY_SEPARATOR . $file);
      }
    }

    return $diffs;
  }

  public static function compareRendered(string $dir1, string $dir2, ?callable $before_match_content = NULL, ?callable $renderer = NULL): ?string {
    $diffs = self::compare($dir1, $dir2, $before_match_content);

    if (empty($diffs['absent_dir1']) && empty($diffs['absent_dir2']) && empty($diffs['content'])) {
      return NULL;
    }

    if ($renderer && is_callable($renderer)) {
      return $renderer($diffs);
    }

    $text = sprintf("Differences between directories \n %s\nand\n%s:\n", $diffs['dir1'], $diffs['dir2']);

    if (!empty($diffs['absent_dir1'])) {
      $text .= "Files absent in dir1:\n";
      foreach (array_keys($diffs['absent_dir1']) as $file) {
        $text .= sprintf("  %s\n", $file);
      }
    }

    if (!empty($diffs['absent_dir2'])) {
      $text .= "Files absent in dir2:\n";
      foreach (array_keys($diffs['absent_dir2']) as $file) {
        $text .= sprintf("  %s\n", $file);
      }
    }

    if (!empty($diffs['content'])) {
      $text .= "Files that differ in content:\n";
      foreach ($diffs['content'] as $file => $data) {
        $text .= sprintf("  %s\n", $file);
        $text .= '--- DIFF START ---' . PHP_EOL;
        $text .= DiffHelper::calculate($data['dir1'], $data['dir2'], 'Unified', [], ['cliColorization' => RendererConstant::CLI_COLOR_DISABLE]);
        $text .= '--- DIFF END ---' . PHP_EOL;
      }
    }

    return $text;
  }

}
