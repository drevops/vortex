<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AssignAuthorPr;
use DrevOps\VortexInstaller\Prompts\Handlers\LabelMergeConflictsPr;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(AssignAuthorPr::class)]
#[CoversClass(LabelMergeConflictsPr::class)]
class PullRequestHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'auto assign pr - prompt' => [
        [AssignAuthorPr::id() => Key::ENTER],
        [AssignAuthorPr::id() => TRUE] + $expected_defaults,
      ],

      'auto assign pr - discovery' => [
        [],
        [AssignAuthorPr::id() => TRUE] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/assign-author.yml');
        },
      ],

      'auto assign pr - discovery - removed' => [
        [],
        [AssignAuthorPr::id() => FALSE] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],

      'auto assign pr - discovery - non-Vortex' => [
        [],
        [AssignAuthorPr::id() => TRUE] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          File::dump(static::$sut . '/.github/workflows/assign-author.yml');
        },
      ],

      'auto assign pr - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          // No assign-author.yml workflow and not installed - fall back.
        },
      ],

      'label merge conflicts - prompt' => [
        [LabelMergeConflictsPr::id() => Key::ENTER],
        [LabelMergeConflictsPr::id() => TRUE] + $expected_defaults,
      ],

      'label merge conflicts - discovery' => [
        [],
        [LabelMergeConflictsPr::id() => TRUE] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/.github/workflows/label-merge-conflict.yml');
        },
      ],

      'label merge conflicts - discovery - removed' => [
        [],
        [LabelMergeConflictsPr::id() => FALSE] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],

      'label merge conflicts - discovery - non-Vortex' => [
        [],
        [LabelMergeConflictsPr::id() => TRUE] + $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          File::dump(static::$sut . '/.github/workflows/label-merge-conflict.yml');
        },
      ],

      'label merge conflicts - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractHandlerDiscoveryTestCase $test): void {
          // No label-merge-conflict.yml workflow and not installed - fall back.
        },
      ],
    ];
  }

}
