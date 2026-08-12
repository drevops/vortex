<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Handlers;

use DrevOps\VortexCli\Prompts\Handlers\Profile;
use DrevOps\VortexCli\Prompts\Handlers\ProfileCustom;
use DrevOps\VortexCli\Utils\Config;
use DrevOps\VortexCli\Utils\File;
use DrevOps\VortexCli\Tests\Support\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Profile::class)]
class ProfileHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'profile - prompt' => [
      [Profile::id() => Key::DOWN . Key::ENTER],
      [Profile::id() => 'minimal'] + $expected_defaults,
    ];
    yield 'profile - prompt - custom' => [
      [Profile::id() => Profile::CUSTOM, ProfileCustom::id() => 'myprofile'],
      [Profile::id() => Profile::CUSTOM, ProfileCustom::id() => 'myprofile'] + $expected_defaults,
    ];
    yield 'profile - prompt - invalid' => [
      [Profile::id() => Profile::CUSTOM, ProfileCustom::id() => 'my profile'],
      'Please enter a valid profile name: only lowercase letters, numbers, and underscores are allowed.',
    ];
    yield 'profile - discovery' => [
      [],
      [Profile::id() => Profile::MINIMAL] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        $test->stubDotenvValue('DRUPAL_PROFILE', Profile::MINIMAL);
      },
    ];
    yield 'profile - discovery - non-Vortex project' => [
      [],
      [Profile::id() => Profile::CUSTOM, ProfileCustom::id() => 'discovered_profile'] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        File::dump(static::$sut . '/web/profiles/discovered_profile/discovered_profile.info');
      },
    ];
    yield 'profile - discovery - invalid' => [
      [],
      $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test): void {
        // No .env file and no profile info files - fall back to default.
      },
    ];
  }

}
