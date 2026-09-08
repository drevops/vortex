<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Starter::class)]
class StarterHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'starter_demo_db' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): string => $test->prompts[Starter::id()] = Starter::LOAD_DATABASE_DEMO),
      static::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'drupal/cms',
        'wikimedia/composer-merge-plugin',
        'vendor/drupal/cms/composer.json',
      ])),
    ];
    yield 'starter_drupal_profile' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): string => $test->prompts[Starter::id()] = Starter::INSTALL_PROFILE_CORE),
      static::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'drupal/cms',
        'wikimedia/composer-merge-plugin',
        'vendor/drupal/cms/composer.json',
      ])),
    ];
    yield 'starter_drupal_cms_profile' => [
      static::cw(fn(AbstractHandlerProcessTestCase $test): string => $test->prompts[Starter::id()] = Starter::INSTALL_PROFILE_DRUPALCMS),
      static::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutContains([
        'drupal/cms',
        'wikimedia/composer-merge-plugin',
        'vendor/drupal/cms/composer.json',
      ])),
    ];
  }

}
