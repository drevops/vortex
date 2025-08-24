<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\Profile;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Profile::class)]
class ProfilePromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'profile - prompt' => [
        [Profile::id() => Key::DOWN . Key::ENTER],
        [Profile::id() => 'minimal'] + $expected_defaults,
      ],

      'profile - prompt - custom' => [
        [Profile::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER . 'myprofile'],
        [Profile::id() => 'myprofile'] + $expected_defaults,
      ],

      'profile - prompt - invalid' => [
        [Profile::id() => Key::DOWN . Key::DOWN . Key::DOWN . Key::ENTER . 'my profile'],
        'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.',
      ],

      'profile - discovery' => [
        [],
        [Profile::id() => Profile::MINIMAL] + $expected_installed,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('DRUPAL_PROFILE', Profile::MINIMAL);
        },
      ],

      'profile - discovery - non-Vortex project' => [
        [],
        [Profile::id() => 'discovered_profile'] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          File::dump(static::$sut . '/web/profiles/discovered_profile/discovered_profile.info');
        },
      ],

      'profile - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No .env file and no profile info files - fall back to default.
        },
      ],
    ];
  }

}
