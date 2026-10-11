<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesAutomerge;
use DrevOps\VortexInstaller\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DependencyUpdatesProvider::class)]
#[CoversClass(DependencyUpdatesAutomerge::class)]
class DependencyUpdatesProviderHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    $all_groups = array_keys(DependencyUpdatesAutomerge::GROUPS);
    yield 'dependency updates provider - prompt' => [
      [DependencyUpdatesProvider::id() => Key::ENTER],
      [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP] + $expected_defaults,
    ];
    yield 'dependency updates provider - discovery - renovate self-hosted - gha' => [
      [],
      [
        DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
        DependencyUpdatesAutomerge::id() => $all_groups,
      ] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/renovate.json');
        File::dump(static::$sut . '/.github/workflows/update-dependencies.yml');
      },
    ];
    yield 'dependency updates provider - discovery - renovate self-hosted - circleci' => [
      [],
      [
        CiProvider::id() => CiProvider::CIRCLECI,
        DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_CI,
        DependencyUpdatesAutomerge::id() => $all_groups,
      ] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/renovate.json');
        File::dump(static::$sut . '/.circleci/config.yml', 'update-dependencies');
      },
    ];
    yield 'dependency updates provider - discovery - renovate app' => [
      [],
      [
        DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP,
        DependencyUpdatesAutomerge::id() => $all_groups,
      ] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/renovate.json');
      },
    ];
    yield 'dependency updates provider - discovery - none' => [
      [],
      [DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
      },
    ];
    yield 'dependency updates provider - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No renovate.json and not installed - should fall back to default.
      },
    ];
    yield 'dependency updates automerge - prompt - deselect first group' => [
      [DependencyUpdatesAutomerge::id() => Key::SPACE . Key::ENTER],
      [DependencyUpdatesAutomerge::id() => array_values(array_diff($all_groups, [DependencyUpdatesAutomerge::PHP]))] + $expected_defaults,
    ];
    yield 'dependency updates automerge - prompt - skipped without renovate' => [
      [DependencyUpdatesProvider::id() => Key::DOWN . Key::DOWN . Key::ENTER],
      [
        DependencyUpdatesProvider::id() => DependencyUpdatesProvider::NONE,
        DependencyUpdatesAutomerge::id() => NULL,
      ] + $expected_defaults,
    ];
    yield 'dependency updates automerge - discovery - flags' => [
      [],
      [
        DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP,
        DependencyUpdatesAutomerge::id() => [DependencyUpdatesAutomerge::DRUPAL_CORE, DependencyUpdatesAutomerge::GITHUB_ACTIONS],
      ] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/renovate.json', (string) json_encode([
          'packageRules' => [
            ['groupSlug' => DependencyUpdatesAutomerge::GROUPS[DependencyUpdatesAutomerge::GITHUB_ACTIONS], 'automerge' => TRUE],
            ['groupSlug' => DependencyUpdatesAutomerge::GROUPS[DependencyUpdatesAutomerge::PHP], 'automerge' => FALSE],
            ['groupSlug' => DependencyUpdatesAutomerge::GROUPS[DependencyUpdatesAutomerge::DRUPAL_CORE], 'automerge' => TRUE],
            ['groupSlug' => DependencyUpdatesAutomerge::GROUPS[DependencyUpdatesAutomerge::JS]],
            ['groupSlug' => 'custom-group', 'automerge' => TRUE],
          ],
        ]));
      },
    ];
    yield 'dependency updates automerge - discovery - no package rules' => [
      [],
      [
        DependencyUpdatesProvider::id() => DependencyUpdatesProvider::RENOVATEBOT_APP,
        DependencyUpdatesAutomerge::id() => $all_groups,
      ] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/renovate.json', '{}');
      },
    ];
  }

}
