<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use AlexSkrypnyk\File\ExtendedSplFileInfo;
use AlexSkrypnyk\File\Tests\Traits\DirectoryAssertionsTrait;
use AlexSkrypnyk\File\Tests\Traits\FileAssertionsTrait;
use AlexSkrypnyk\PhpunitHelpers\Traits\SerializableClosureTrait;
use AlexSkrypnyk\PhpunitHelpers\UnitTestCase as UpstreamUnitTestCase;
use DrevOps\VortexInstaller\Utils\File;

/**
 * Class UnitTestCase.
 *
 * UnitTestCase fixture class.
 *
 * phpcs:disable Drupal.Commenting.FunctionComment.Missing
 * phpcs:disable Drupal.Commenting.DocComment.MissingShort
 */
abstract class UnitTestCase extends UpstreamUnitTestCase {

  use SerializableClosureTrait;
  use DirectoryAssertionsTrait;
  use FileAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $cwd = getcwd();
    if ($cwd === FALSE) {
      throw new \RuntimeException('Failed to determine current working directory.');
    }

    // Run tests from the root of the repo.
    self::locationsInit($cwd . '/../../');
  }

  /**
   * {@inheritdoc}
   */
  public static function locationsFixturesDir(): string {
    return '.vortex/installer/tests/Fixtures';
  }

  protected static function replaceVersions(string $directory): void {
    File::replaceContentAsync(function (string $content, ExtendedSplFileInfo $file): string {
      // Skip processing files that are not relevant for version replacement.
      $extension = $file->getExtension();
      if (!in_array($extension, ['json', 'yml', 'yaml', 'dockerfile'], TRUE)) {
        return $content;
      }

      return static::replaceVersionsInLine($content);
    });

    // Execute all queued async operations.
    File::runTaskDirectory($directory);
  }

  protected static function replaceVersionsInLine(string $content): string {
    $patterns = [
      '/sha512\-[A-Za-z0-9+\/]{86}={0,2}/' => '__INTEGRITY__',

      // GitHub Actions with digests and version comments.
      '/([\w.-]+\/[\w.-]+)@[a-f0-9]{40}\s*#\s*v\d+(?:\.\d+)*/' => '${1}@__HASH__ # __VERSION__',
      // GitHub Actions with digests (no version comments).
      '/([\w.-]+\/[\w.-]+)@[a-f0-9]{40}/' => '${1}@__HASH__',

      '/#[a-fA-F0-9]{39,40}/' => '#__HASH__',
      '/@[a-fA-F0-9]{39,40}/' => '@__HASH__',

      // composer.json and package.json.
      '/": "(?:\^|~|>=|<=)?\d+(?:\.\d+){0,2}(?:(?:-|@)[\w.-]+)?"/' => '": "__VERSION__"',
      // Docker images with digests. Must come before regular docker image
      // pattern.
      '/([\w.-]+\/[\w.-]+:)(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?@sha256:[a-f0-9]{64}/' => '${1}__VERSION__',
      // docker-compose.yml.
      '/([\w.-]+\/[\w.-]+:)(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?/' => '${1}__VERSION__',
      '/([\w.-]+\/[\w.-]+:)canary$/m' => '${1}__VERSION__',
      // GHAs.
      '/([\w.-]+\/[\w.-]+)@(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?/' => '${1}@__VERSION__',
      '/(node-version:\s)(?:v)?\d+(?:\.\d+){0,2}(?:-[\w.-]+)?/' => '${1}__VERSION__',

      // Catch all.
      '/(?:\^|~)?v?\d+\.\d+\.\d+(?:(?:-|@)[\w.-]+)?/' => '__VERSION__',
    ];

    // Apply patterns based on file name regex matches.
    $replaced = 0;
    foreach ($patterns as $pattern => $replacement) {
      $original_content = $content;

      $content = preg_replace($pattern, $replacement, $content);

      if ($content !== $original_content) {
        $replaced++;
      }

      // Early exit after 4 pattern replacements to prevent excessive
      // replacements and optimize performance. The threshold of 4 was chosen
      // based on typical file modification patterns - most files require 1-3
      // replacements, so 4 provides a reasonable safety margin while preventing
      // runaway operations.
      if ($replaced > 4) {
        break;
      }
    }

    return $content;
  }

}
