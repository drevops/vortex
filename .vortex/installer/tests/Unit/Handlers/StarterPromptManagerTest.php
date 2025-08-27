<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Starter::class)]
class StarterPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'starter - prompt' => [
        [Starter::id() => Key::ENTER],
        [Starter::id() => Starter::DRUPAL_LOAD_DATABASE_DEMO] + $expected_defaults,
      ],

      'starter - prompt - profile' => [
        [Starter::id() => Key::DOWN . Key::ENTER],
        [Starter::id() => Starter::DRUPAL_INSTALL_PROFILE] + $expected_defaults,
      ],

      'starter - discovery' => [
        [],
        [Starter::id() => Starter::DRUPAL_LOAD_DATABASE_DEMO] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // Noop.
        },
      ],
    ];
  }

}
