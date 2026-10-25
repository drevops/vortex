<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\Storybook;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Storybook::class)]
class StorybookHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'storybook_enabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): true => $test->prompts[Storybook::id()] = TRUE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(static::$sut . '/web/modules/custom/sw_base/src/Plugin/DeployStep/BuildStorybookDeployStep.php');
          $test->assertFileExists(static::$sut . '/.docker/config/nginx/storybook.conf');
          $test->assertFileExists(static::$sut . '/web/sites/default/includes/modules/settings.storybook.php');
          $test->assertFileExists(static::$sut . '/web/themes/custom/star_wars/.storybook/main.mjs');
          $test->assertFileContainsString(static::$sut . '/composer.json', 'drupal/storybook');
          $test->assertFileContainsString(static::$sut . '/web/themes/custom/star_wars/package.json', '"webpack-dev-middleware"');
          $test->assertFileContainsString(static::$sut . '/.ahoy.yml', 'storybook-build');
          $test->assertFileContainsString(static::$sut . '/.ahoy.yml', 'VORTEX_HOST_STORYBOOK_PORT');
          $test->assertFileContainsString(static::$sut . '/web/modules/custom/sw_base/src/Plugin/DeployStep/EnableDevelopmentModulesDeployStep.php', "'storybook',");
      }),
    ];

    yield 'storybook_disabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): false => $test->prompts[Storybook::id()] = FALSE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/web/modules/custom/sw_base/src/Plugin/DeployStep/BuildStorybookDeployStep.php');
          $test->assertFileDoesNotExist(static::$sut . '/.docker/config/nginx/storybook.conf');
          $test->assertFileDoesNotExist(static::$sut . '/web/sites/default/includes/modules/settings.storybook.php');
          $test->assertDirectoryDoesNotExist(static::$sut . '/web/themes/custom/star_wars/.storybook');
          $test->assertFileNotContainsString(static::$sut . '/composer.json', 'drupal/storybook');
          $test->assertFileNotContainsString(static::$sut . '/web/themes/custom/star_wars/package.json', '"overrides"');
          $test->assertFileNotContainsString(static::$sut . '/web/themes/custom/star_wars/package-lock.json', 'webpack-dev-middleware');
          $test->assertFileNotContainsString(static::$sut . '/.ahoy.yml', 'storybook-build');
          $test->assertFileNotContainsString(static::$sut . '/.ahoy.yml', 'VORTEX_HOST_STORYBOOK_PORT');
          $test->assertFileNotContainsString(static::$sut . '/web/modules/custom/sw_base/src/Plugin/DeployStep/EnableDevelopmentModulesDeployStep.php', 'storybook');
          $test->assertFileNotContainsString(static::$sut . '/web/modules/custom/sw_base/src/Plugin/DeployStep/EnableDevelopmentModulesDeployStep.php', 'development_settings');
      }),
    ];
  }

}
