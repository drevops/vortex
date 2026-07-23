<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use DrevOps\VortexCli\Utils\File;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class ThemeHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'theme_olivero' => [
      self::cw(fn($test): string => $test->prompts['theme'] = 'olivero'),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertDirectoryNotContainsString(self::$sut, 'themes/custom', [
        '.gitignore',
        'scripts/vortex',
        'composer.json',
        'AGENTS.md',
        'CLAUDE.md',
      ])),
    ];
    yield 'theme_claro' => [
      self::cw(fn($test): string => $test->prompts['theme'] = 'claro'),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertDirectoryNotContainsString(self::$sut, 'themes/custom', [
        '.gitignore',
        'scripts/vortex',
        'composer.json',
        'AGENTS.md',
        'CLAUDE.md',
      ])),
    ];
    yield 'theme_stark' => [
      self::cw(fn($test): string => $test->prompts['theme'] = 'stark'),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertDirectoryNotContainsString(self::$sut, 'themes/custom', [
        '.gitignore',
        'scripts/vortex',
        'composer.json',
        'AGENTS.md',
        'CLAUDE.md',
      ])),
    ];
    yield 'theme_custom' => [
      self::cw(function ($test): void {
          $test->prompts['theme'] = 'custom';
          $test->prompts['theme_custom'] = 'light_saber';
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertDirectoryNotContainsString(self::$sut, 'your_site_theme')),
    ];
    yield 'theme_custom_non_vortex' => [
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
        $test->prompts['theme'] = 'custom';
        $test->prompts['theme_custom'] = 'star_wars';

        // Run a first install to create a proper Vortex project
        // with a Vortex-sourced custom theme.
        $test->runInstall();

        $theme_dir = self::$sut . '/web/themes/custom/star_wars';
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
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
        // The project's own theme files must be preserved unchanged -
        // Vortex must not overwrite them with its template theme files.
        $theme_dir = self::$sut . '/web/themes/custom/star_wars';
        $test->assertDirectoryExists($theme_dir);
        $test->assertStringEqualsFile($theme_dir . '/star_wars.info.yml', 'name: Star Wars Custom' . PHP_EOL . 'type: theme' . PHP_EOL . 'core_version_requirement: ^11' . PHP_EOL);
        $test->assertStringEqualsFile($theme_dir . '/package.json', '{"name": "star_wars_custom"}' . PHP_EOL);
        $test->assertStringEqualsFile($theme_dir . '/styles.css', '.star-wars { color: blue; }' . PHP_EOL);

        $test->assertFilesDoNotExist($theme_dir, ['star_wars.theme'], 'Vortex template theme files should not be present');
      }),
    ];
  }

}
