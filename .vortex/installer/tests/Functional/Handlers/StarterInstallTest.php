<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use DrevOps\VortexInstaller\Utils\Env;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Starter::class)]
class StarterInstallTest extends AbstractInstallTestCase {

  public static function dataProviderInstall(): array {
    return [
      'starter, demo db' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Starter::id()), Starter::LOAD_DATABASE_DEMO)),
      ],

      'starter, Drupal profile' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Starter::id()), Starter::INSTALL_PROFILE_CORE)),
      ],

      'starter, Drupal CMS profile' => [
        static::cw(fn() => Env::put(PromptManager::makeEnvName(Starter::id()), Starter::INSTALL_PROFILE_DRUPALCMS)),
        static::cw(fn(FunctionalTestCase $test) => $test->assertSutNotContains([
          'drupal/cms',
          'wikimedia/composer-merge-plugin',
          'vendor/drupal/cms/composer.json',
        ])),
      ],
    ];
  }

}
