<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Modules;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Converter;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Modules::class)]
class ModulesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): array {
    return [
      'modules_no_admin_toolbar' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('admin_toolbar');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('drupal/admin_toolbar')),
      ],

      'modules_no_coffee' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('coffee');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('coffee')),
      ],

      'modules_no_config_split' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('config_split');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('config_split')),
      ],

      'modules_no_config_update' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('config_update');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('config_update')),
      ],

      'modules_no_environment_indicator' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('environment_indicator');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('environment_indicator')),
      ],

      'modules_no_pathauto' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('pathauto');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('drupal/pathauto')),
      ],

      'modules_no_redirect' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('redirect');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('drupal/redirect')),
      ],

      'modules_no_robotstxt' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('robotstxt');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('robotstxt')),
      ],

      'modules_no_seckit' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('seckit');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('seckit')),
      ],

      'modules_no_shield' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('shield');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('shield')),
      ],

      'modules_no_stage_file_proxy' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept('stage_file_proxy');
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('stage_file_proxy')),
      ],

      'modules_no_seckit_shield_stage_file_proxy' => [
        static::cw(function (): void {
          $selected_modules = static::getModulesExcept(['seckit', 'shield', 'stage_file_proxy']);
          Env::put(Modules::envName(), Converter::toList($selected_modules));
        }),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'seckit',
          'shield',
          'stage_file_proxy',
        ])),
      ],

      'modules_none' => [
        static::cw(fn() => Env::put(Modules::envName(), ',')),
        static::cw(function (FunctionalTestCase $test): void {
          foreach (array_keys(Modules::getAvailableModules()) as $module) {
            // Cannot assert by the module name alone, as some module names
            // are generic words that may appear elsewhere.
            $test->assertSutNotContains('drupal/' . $module);
          }
        }),
      ],

    ];
  }

  /**
   * Get modules list with specified modules removed.
   *
   * @param array|string $modules_to_remove
   *   Module name(s) to remove from the full list.
   *
   * @return array
   *   Array of module names with specified modules removed.
   */
  protected static function getModulesExcept(array|string $modules_to_remove): array {
    $all_modules = array_keys(Modules::getAvailableModules());
    $remove = is_array($modules_to_remove) ? $modules_to_remove : [$modules_to_remove];
    return array_values(array_diff($all_modules, $remove));
  }

}
