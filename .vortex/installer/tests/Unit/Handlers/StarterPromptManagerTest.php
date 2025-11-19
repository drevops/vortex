<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Utils\Config;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Starter::class)]
class StarterPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'starter - prompt' => [
        [Starter::id() => Key::ENTER],
        [Starter::id() => Starter::LOAD_DATABASE_DEMO] + $expected_defaults,
      ],

      'starter - prompt - Drupal profile' => [
        [Starter::id() => Key::DOWN . Key::ENTER],
        [Starter::id() => Starter::INSTALL_PROFILE_CORE] + $expected_defaults,
      ],
      'starter - prompt - Drupal CMS profile' => [
        [Starter::id() => Key::DOWN . Key::DOWN . Key::ENTER],
        [
          Starter::id() => Starter::INSTALL_PROFILE_DRUPALCMS,
          Profile::id() => Starter::INSTALL_PROFILE_DRUPALCMS_PATH,
        ] + $expected_defaults,

      ],

      'starter - discovery' => [
        [],
        [Starter::id() => Starter::LOAD_DATABASE_DEMO] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // Noop.
        },
      ],

      'starter - installed project - skipped' => [
        [],
        [Starter::id() => Starter::LOAD_DATABASE_DEMO] + static::getExpectedInstalled(),
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
        },
      ],
    ];
  }

}
