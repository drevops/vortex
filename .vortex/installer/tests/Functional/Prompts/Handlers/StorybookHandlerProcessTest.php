<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Storybook;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Storybook::class)]
class StorybookHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'storybook_enabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): true => $test->prompts[Storybook::id()] = TRUE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileExists(static::$sut . '/scripts/provision-50-storybook.sh');
          $test->assertFileExists(static::$sut . '/.docker/config/nginx/storybook.conf');
          $test->assertFileExists(static::$sut . '/web/sites/default/includes/modules/settings.storybook.php');
          $test->assertFileExists(static::$sut . '/web/themes/custom/star_wars/.storybook/main.js');
          $test->assertFileContainsString(static::$sut . '/composer.json', 'drupal/storybook');
          $test->assertFileContainsString(static::$sut . '/.ahoy.yml', 'storybook-build');
      }),
    ];

    yield 'storybook_disabled' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): false => $test->prompts[Storybook::id()] = FALSE),
      static::cw(function (AbstractHandlerProcessTestCase $test): void {
          $test->assertFileDoesNotExist(static::$sut . '/scripts/provision-50-storybook.sh');
          $test->assertFileDoesNotExist(static::$sut . '/.docker/config/nginx/storybook.conf');
          $test->assertFileDoesNotExist(static::$sut . '/web/sites/default/includes/modules/settings.storybook.php');
          $test->assertDirectoryDoesNotExist(static::$sut . '/web/themes/custom/star_wars/.storybook');
          $test->assertFileNotContainsString(static::$sut . '/composer.json', 'drupal/storybook');
          $test->assertFileNotContainsString(static::$sut . '/.ahoy.yml', 'storybook-build');
      }),
    ];
  }

}
