<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional\Handlers;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;

#[Group('snapshot')]
#[CoversNothing]
final class StarterHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'starter_demo_db' => [
      self::cw(fn($test): string => $test->prompts['starter'] = 'load_demodb'),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'drupal/cms',
        'wikimedia/composer-merge-plugin',
        'vendor/drupal/cms/composer.json',
      ])),
    ];
    yield 'starter_drupal_profile' => [
      self::cw(fn($test): string => $test->prompts['starter'] = 'install_profile_core'),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutNotContains([
        'drupal/cms',
        'wikimedia/composer-merge-plugin',
        'vendor/drupal/cms/composer.json',
      ])),
    ];
    yield 'starter_drupal_cms_profile' => [
      self::cw(fn($test): string => $test->prompts['starter'] = 'install_profile_drupalcms'),
      self::cw(fn(AbstractHandlerProcessTestCase $test) => $test->assertSutContains([
        'drupal/cms',
        'wikimedia/composer-merge-plugin',
        'vendor/drupal/cms/composer.json',
      ])),
    ];
  }

}
