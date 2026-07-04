<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use DrevOps\VortexCli\Handler\Modules;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class ModulesHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'modules_no_admin_toolbar' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('admin_toolbar');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/admin_toolbar')),
    ];
    yield 'modules_no_coffee' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('coffee');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('coffee')),
    ];
    yield 'modules_no_config_split' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('config_split');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('config_split')),
    ];
    yield 'modules_no_config_update' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('config_update');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('config_update')),
    ];
    yield 'modules_no_devel' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('devel');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/devel')),
    ];
    yield 'modules_no_drupal_helpers' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('drupal_helpers');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/drupal_helpers')),
    ];
    yield 'modules_no_environment_indicator' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('environment_indicator');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('environment_indicator')),
    ];
    yield 'modules_no_generated_content' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('generated_content');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/generated_content')),
    ];
    yield 'modules_no_pathauto' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('pathauto');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/pathauto')),
    ];
    yield 'modules_no_redirect' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('redirect');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/redirect')),
    ];
    yield 'modules_no_reroute_email' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('reroute_email');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/reroute_email')),
    ];
    yield 'modules_no_robotstxt' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('robotstxt');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('robotstxt')),
    ];
    yield 'modules_no_sdc_devel' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('sdc_devel');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/sdc_devel')),
    ];
    yield 'modules_no_seckit' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('seckit');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('seckit')),
    ];
    yield 'modules_no_shield' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('shield');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('shield')),
    ];
    yield 'modules_no_stage_file_proxy' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('stage_file_proxy');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('stage_file_proxy')),
    ];
    yield 'modules_no_testmode' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('testmode');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/testmode')),
    ];
    yield 'modules_no_xmlsitemap' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept('xmlsitemap');
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains('drupal/xmlsitemap')),
    ];
    yield 'modules_no_seckit_shield_stage_file_proxy' => [
      self::cw(function ($test): void {
          $test->prompts['modules'] = self::getModulesExcept(['seckit', 'shield', 'stage_file_proxy']);
      }),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'seckit',
        'shield',
        'stage_file_proxy',
      ])),
    ];
    yield 'modules_none' => [
      self::cw(fn($test): array => $test->prompts['modules'] = []),
      self::cw(function (AbstractHandlerProcessTestCase $test): void {
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
