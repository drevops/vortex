<?php

namespace Drevops\Installer\Tests\Functional;

use PHPUnit\Framework\TestCase;

/**
 * Class ScriptUnitTestBase.
 *
 * Base class to unit tests scripts.
 *
 * @group scripts
 */
abstract class FunctionalTestBase extends TestCase {

  /**
   * Script to include.
   *
   * @var string
   */
  protected $script;

  /**
   * Temporary directory.
   *
   * @var string
   */
  protected $tmpDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (!is_readable($this->script)) {
      throw new \RuntimeException(sprintf('Unable to include script file %s.', $this->script));
    }
    require_once $this->script;

    $this->tmpDir = $this->tempdir();

    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    if (!empty($this->tmpDir)) {
      @unlink($this->tmpDir);
    }
  }

  /**
   * Run script with optional arguments.
   *
   * @param array $args
   *   Optional array of arguments to pass to the script.
   * @param bool $verbose
   *   Optional flag to enable verbose output in the script.
   *
   * @return array
   *   Array with the following keys:
   *   - code: (int) Exit code.
   *   - output: (string) Output.
   */
  protected function runScript(array $args = [], $verbose = FALSE) {
    putenv('SCRIPT_RUN_SKIP=0');
    if ($verbose) {
      putenv('SCRIPT_QUIET=0');
    }
    $command = sprintf('php %s %s', $this->script, implode(' ', $args));
    $output = [];
    $result_code = 1;
    exec($command, $output, $result_code);
    return [
      'code' => $result_code,
      'output' => implode(PHP_EOL, $output),
    ];
  }

  /**
   * Enable script run.
   */
  protected function enableScriptRun() {
    putenv('SCRIPT_RUN_SKIP');
    putenv('SCRIPT_QUIET');
  }

  /**
   * Disable script run.
   */
  protected function disableScriptRun() {
    putenv('SCRIPT_RUN_SKIP=1');
    putenv('SCRIPT_QUIET=1');
  }

  /**
   * Replace path to a fixture file.
   */
  protected function fixtureFile($filename) {
    $path = 'tests/phpunit/fixtures/drupal_configs/' . $filename;
    if (!is_readable($path)) {
      throw new \RuntimeException(sprintf('Unable to find fixture file %s.', $path));
    }
    return $path;
  }

  /**
   * Path to a temporary file.
   */
  protected function toTmpPath($filename, $prefix = NULL) {
    return $prefix
      ? $this->tmpDir . DIRECTORY_SEPARATOR . $prefix . DIRECTORY_SEPARATOR . $filename
      : $this->tmpDir . DIRECTORY_SEPARATOR . $filename;
  }

  /**
   * Print the contents of the temporary directory.
   */
  protected function printTempDir() {
    $it = new \RecursiveTreeIterator(new \RecursiveDirectoryIterator($this->tmpDir, \RecursiveDirectoryIterator::SKIP_DOTS));
    print PHP_EOL;
    foreach ($it as $value) {
      print $value . PHP_EOL;
    }
  }

  /**
   * Create a random unique temporary directory.
   */
  protected function tempdir($dir = NULL, $prefix = 'tmp_', $mode = 0700, $max_attempts = 1000) {
    if (is_null($dir)) {
      $dir = sys_get_temp_dir();
    }

    $dir = rtrim($dir, DIRECTORY_SEPARATOR);

    if (!is_dir($dir) || !is_writable($dir)) {
      return FALSE;
    }

    if (strpbrk($prefix, '\\/:*?"<>|') !== FALSE) {
      return FALSE;
    }
    $attempts = 0;

    do {
      $path = sprintf('%s%s%s%s', $dir, DIRECTORY_SEPARATOR, $prefix, mt_rand(100000, mt_getrandmax()));
    } while (!mkdir($path, $mode) && $attempts++ < $max_attempts);

    if (!is_dir($path) || !is_writable($path)) {
      throw new \RuntimeException(sprintf('Unable to create temporary directory "%s".', $path));
    }

    return $path;
  }

  /**
   * Recursively replace a value in the array using provided callback.
   */
  protected function arrayReplaceValue($array, $cb) {
    foreach ($array as $k => $item) {
      if (is_array($item)) {
        $array[$k] = $this->arrayReplaceValue($item, $cb);
      }
      else {
        $array[$k] = $cb($item);
      }
    }

    return $array;
  }

  /**
   * Create temp files from fixtures.
   *
   * @param array $fixture_map
   *   Array of fixture mappings the following structure:
   *   - key: (string) Path to create.
   *   - value: (string) Path to a fixture file to use.
   * @param string $prefix
   *   Optional directory prefix.
   *
   * @return array
   *   Array of created files with the following structure:
   *   - key: (string) Source path (the key from $file_structure).
   *   - value: (string) Path to a fixture file to use.
   */
  protected function createTmpFilesFromFixtures(array $fixture_map, $prefix = NULL) {
    $files = [];
    foreach ($fixture_map as $path => $fixture_file) {
      $tmp_path = $this->toTmpPath($path, $prefix);
      $dirname = dirname($tmp_path);

      if (!file_exists($dirname)) {
        mkdir($dirname, 0777, TRUE);
        if (!is_readable($dirname)) {
          throw new \RuntimeException(sprintf('Unable to create temp directory %s.', $dirname));
        }
      }

      // Pass-through preserving/removal values.
      if (is_bool($fixture_file)) {
        $files[$path] = $fixture_file;
        continue;
      }

      // Allow creating empty directories.
      if (empty($fixture_file) || $fixture_file === '.empty') {
        continue;
      }
      $fixture_file = $this->fixtureFile($fixture_file);

      copy($fixture_file, $tmp_path);
      $files[$path] = $tmp_path;
    }

    return $files;
  }

  /**
   * Create temp files from fixtures.
   *
   * @param array $fixture_map
   *   Array of fixture mappings the following structure:
   *   - key: (string) Path to create.
   *   - value: (string) Path to a fixture file to use.
   * @param string $prefix
   *   Optional directory prefix.
   *
   * @return array
   *   Array of created files with the following structure:
   *   - key: (string) Source path (the key from $file_structure).
   *   - value: (string) Path to a fixture file to use.
   */
  protected function replaceFixturePaths(array $fixture_map, $prefix = NULL) {
    foreach ($fixture_map as $k => $v) {
      if (is_array($v)) {
        $fixture_map[$k] = $this->replaceFixturePaths($v, $prefix);
      }
      else {
        $tmp_path = $this->toTmpPath($v, $prefix);

        $dirname = dirname($tmp_path);
        if (!file_exists($dirname)) {
          mkdir($dirname, 0777, TRUE);
          if (!is_readable($dirname)) {
            throw new \RuntimeException(sprintf('Unable to create temp directory %s.', $dirname));
          }
        }

        // Pass-through preserving/removal values.
        if (is_bool($v)) {
          $fixture_map[$k] = $v;
          continue;
        }

        // Allow creating empty directories.
        if (empty($v) || $v === '.empty') {
          continue;
        }

        $fixture_file = $this->fixtureFile($v);
        copy($fixture_file, $tmp_path);

        $fixture_map[$k] = $tmp_path;
      }
    }

    return $fixture_map;
  }

}
