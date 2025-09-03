<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsOnboarding;
use DrevOps\VortexInstaller\Prompts\Handlers\PreserveDocsProject;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(PreserveDocsProject::class)]
#[CoversClass(PreserveDocsOnboarding::class)]
class DocsPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'preserve project documentation - prompt' => [
        [PreserveDocsProject::id() => Key::ENTER],
        [PreserveDocsProject::id() => TRUE] + $expected_defaults,
      ],

      'preserve project documentation - discovery' => [
        [],
        [PreserveDocsProject::id() => TRUE] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docs/README.md');
        },
      ],

      'preserve project documentation - discovery - removed' => [
        [],
        [PreserveDocsProject::id() => FALSE] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],

      'preserve project documentation - discovery - non-Vortex' => [
        [],
        [PreserveDocsProject::id() => TRUE] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/docs/README.md');
        },
      ],

      'preserve project documentation - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No docs/README.md and not installed - should fall back to default.
        },
      ],

      'preserve onboarding checklist - prompt' => [
        [PreserveDocsOnboarding::id() => Key::ENTER],
        [PreserveDocsOnboarding::id() => TRUE] + $expected_defaults,
      ],

      'preserve onboarding checklist - discovery' => [
        [],
        [PreserveDocsOnboarding::id() => TRUE] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          File::dump(static::$sut . '/docs/onboarding.md');
        },
      ],

      'preserve onboarding checklist - discovery - removed' => [
        [],
        [PreserveDocsOnboarding::id() => FALSE] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],

      'preserve onboarding checklist - discovery - non-Vortex' => [
        [],
        [PreserveDocsOnboarding::id() => TRUE] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/docs/onboarding.md');
        },
      ],

      'preserve onboarding checklist - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No docs/onboarding.md and not installed - fall back to default.
        },
      ],
    ];
  }

}
