<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\CiProvider;
use DrevOps\VortexCli\Prompts\Handlers\DependencyUpdatesAutomerge;
use DrevOps\VortexCli\Prompts\Handlers\DependencyUpdatesProvider;
use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(DependencyUpdatesProvider::class)]
#[CoversClass(DependencyUpdatesAutomerge::class)]
class DependencyUpdatesProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'deps_updates_provider_ci_gha' => [
      static::cw(fn($test): string => $test->prompts[DependencyUpdatesProvider::id()] = DependencyUpdatesProvider::RENOVATEBOT_CI),
    ];
    yield 'deps_updates_provider_ci_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[DependencyUpdatesProvider::id()] = DependencyUpdatesProvider::RENOVATEBOT_CI;
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
    ];
    yield 'deps_updates_provider_app' => [
      static::cw(fn($test): string => $test->prompts[DependencyUpdatesProvider::id()] = DependencyUpdatesProvider::RENOVATEBOT_APP),
    ];
    yield 'deps_updates_provider_none' => [
      static::cw(fn($test): string => $test->prompts[DependencyUpdatesProvider::id()] = DependencyUpdatesProvider::NONE),
    ];
    yield 'deps_updates_automerge_some' => [
      static::cw(fn($test): array => $test->prompts[DependencyUpdatesAutomerge::id()] = [DependencyUpdatesAutomerge::DRUPAL_CORE, DependencyUpdatesAutomerge::CI_TOOLS, DependencyUpdatesAutomerge::GITHUB_ACTIONS]),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $config = json_decode(File::read(static::$sut . '/renovate.json'), TRUE);
          $test->assertSame([
            'php-all-except-core-minor-patch' => FALSE,
            'php-drupal-core-minor-patch' => TRUE,
            'js-all-minor-patch' => FALSE,
            'container-images-all-major-minor-patch' => FALSE,
            'ci-tools-all-major-minor-patch' => TRUE,
            'ci-tools-renovate-major-minor-patch' => TRUE,
            'github-actions-all-major-minor-patch' => TRUE,
          ], array_column($config['packageRules'], 'automerge', 'groupSlug'));
      }),
    ];
    yield 'deps_updates_automerge_none' => [
      static::cw(fn($test): array => $test->prompts[DependencyUpdatesAutomerge::id()] = []),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $config = json_decode(File::read(static::$sut . '/renovate.json'), TRUE);
          $test->assertSame([
            'php-all-except-core-minor-patch' => FALSE,
            'php-drupal-core-minor-patch' => FALSE,
            'js-all-minor-patch' => FALSE,
            'container-images-all-major-minor-patch' => FALSE,
            'ci-tools-all-major-minor-patch' => FALSE,
            'ci-tools-renovate-major-minor-patch' => FALSE,
            'github-actions-all-major-minor-patch' => FALSE,
          ], array_column($config['packageRules'], 'automerge', 'groupSlug'));
          $test->assertSame([
            'php-all-except-core-minor-patch' => '14 days',
            'js-all-minor-patch' => '14 days',
            'container-images-all-major-minor-patch' => '14 days',
            'ci-tools-all-major-minor-patch' => '14 days',
            'ci-tools-renovate-major-minor-patch' => '14 days',
            'github-actions-all-major-minor-patch' => '14 days',
          ], array_column($config['packageRules'], 'minimumReleaseAge', 'groupSlug'));
      }),
    ];
  }

}
