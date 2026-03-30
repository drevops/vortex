<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Modules;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Modules::class)]
class ModulesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'modules_no_admin_toolbar' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('admin_toolbar');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('drupal/admin_toolbar')),
    ];
    yield 'modules_no_coffee' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('coffee');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('coffee')),
    ];
    yield 'modules_no_config_split' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('config_split');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('config_split')),
    ];
    yield 'modules_no_config_update' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('config_update');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('config_update')),
    ];
    yield 'modules_no_environment_indicator' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('environment_indicator');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('environment_indicator')),
    ];
    yield 'modules_no_pathauto' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('pathauto');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('drupal/pathauto')),
    ];
    yield 'modules_no_redirect' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('redirect');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('drupal/redirect')),
    ];
    yield 'modules_no_robotstxt' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('robotstxt');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('robotstxt')),
    ];
    yield 'modules_no_seckit' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('seckit');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('seckit')),
    ];
    yield 'modules_no_shield' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('shield');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('shield')),
    ];
    yield 'modules_no_stage_file_proxy' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept('stage_file_proxy');
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains('stage_file_proxy')),
    ];
    yield 'modules_no_seckit_shield_stage_file_proxy' => [
      static::cw(function ($test): void {
          $test->prompts[Modules::id()] = static::getModulesExcept(['seckit', 'shield', 'stage_file_proxy']);
      }),
      static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
        'seckit',
        'shield',
        'stage_file_proxy',
      ])),
    ];
    yield 'modules_none' => [
      static::cw(fn($test): array => $test->prompts[Modules::id()] = []),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
        foreach (array_keys(Modules::getAvailableModules()) as $module) {
          // Cannot assert by the module name alone, as some module names
          // are generic words that may appear elsewhere.
          $test->assertSutNotContains('drupal/' . $module);
        }
      }),
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
