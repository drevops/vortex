<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Theme;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Theme::class)]
class ThemeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'theme_olivero' => [
      static::cw(fn() => Env::put(Theme::envName(), Theme::OLIVERO)),
      static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'themes/custom', [
        '.gitignore',
        'scripts/vortex',
        'composer.json',
        'AGENTS.md',
        'CLAUDE.md',
      ])),
    ];
    yield 'theme_claro' => [
      static::cw(fn() => Env::put(Theme::envName(), Theme::CLARO)),
      static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'themes/custom', [
        '.gitignore',
        'scripts/vortex',
        'composer.json',
        'AGENTS.md',
        'CLAUDE.md',
      ])),
    ];
    yield 'theme_stark' => [
      static::cw(fn() => Env::put(Theme::envName(), Theme::STARK)),
      static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'themes/custom', [
        '.gitignore',
        'scripts/vortex',
        'composer.json',
        'AGENTS.md',
        'CLAUDE.md',
      ])),
    ];
    yield 'theme_custom' => [
      static::cw(fn() => Env::put(Theme::envName(), 'light_saber')),
      static::cw(fn(FunctionalTestCase $test) => $test->assertDirectoryNotContainsString(static::$sut, 'your_site_theme')),
    ];
    yield 'theme_custom_non_vortex' => [
      static::cw(function (FunctionalTestCase $test): void {
        Env::put(Theme::envName(), 'star_wars');

        // Run a first install to create a proper Vortex project
        // with a Vortex-sourced custom theme.
        $test->runNonInteractiveInstall();

        $theme_dir = static::$sut . '/web/themes/custom/star_wars';
        $test->assertFilesExist($theme_dir, ['star_wars.theme'], 'Vortex template theme files should be present');

        // Replace the Vortex-installed theme files with custom
        // non-Vortex theme files (simulating a project that replaced the
        // Vortex theme scaffold with their own).
        File::remove($theme_dir);
        File::mkdir($theme_dir);
        File::dump($theme_dir . '/star_wars.info.yml', 'name: Star Wars Custom' . PHP_EOL . 'type: theme' . PHP_EOL . 'core_version_requirement: ^11' . PHP_EOL);
        File::dump($theme_dir . '/package.json', '{"name": "star_wars_custom"}' . PHP_EOL);
        File::dump($theme_dir . '/styles.css', '.star-wars { color: blue; }' . PHP_EOL);
      }),
      static::cw(function (FunctionalTestCase $test): void {
        // The project's own theme files must be preserved unchanged —
        // Vortex must not overwrite them with its template theme files.
        $theme_dir = static::$sut . '/web/themes/custom/star_wars';
        $test->assertDirectoryExists($theme_dir);
        $test->assertStringEqualsFile($theme_dir . '/star_wars.info.yml', 'name: Star Wars Custom' . PHP_EOL . 'type: theme' . PHP_EOL . 'core_version_requirement: ^11' . PHP_EOL);
        $test->assertStringEqualsFile($theme_dir . '/package.json', '{"name": "star_wars_custom"}' . PHP_EOL);
        $test->assertStringEqualsFile($theme_dir . '/styles.css', '.star-wars { color: blue; }' . PHP_EOL);

        $test->assertFilesDoNotExist($theme_dir, ['star_wars.theme'], 'Vortex template theme files should not be present');
      }),
    ];
  }

}
