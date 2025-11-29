<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\NotificationChannels;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(NotificationChannels::class)]
class NotificationChannelsHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();

    return [
      'notification channels - prompt' => [
        [NotificationChannels::id() => Key::ENTER],
        $expected_defaults,
      ],

      'notification channels - discovery - email only' => [
        [],
        [NotificationChannels::id() => [NotificationChannels::EMAIL]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('VORTEX_NOTIFY_CHANNELS', Converter::toList([NotificationChannels::EMAIL]));
        },
      ],

      'notification channels - discovery - slack only' => [
        [],
        [NotificationChannels::id() => [NotificationChannels::SLACK]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('VORTEX_NOTIFY_CHANNELS', Converter::toList([NotificationChannels::SLACK]));
        },
      ],

      'notification channels - discovery - multiple' => [
        [],
        [NotificationChannels::id() => [NotificationChannels::EMAIL, NotificationChannels::GITHUB, NotificationChannels::SLACK]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('VORTEX_NOTIFY_CHANNELS', Converter::toList([NotificationChannels::EMAIL, NotificationChannels::SLACK, NotificationChannels::GITHUB]));
        },
      ],

      'notification channels - discovery - all channels' => [
        [],
        [
          NotificationChannels::id() => [
            NotificationChannels::EMAIL,
            NotificationChannels::GITHUB,
            NotificationChannels::JIRA,
            NotificationChannels::NEWRELIC,
            NotificationChannels::SLACK,
            NotificationChannels::WEBHOOK,
          ],
        ] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          $test->stubDotenvValue('VORTEX_NOTIFY_CHANNELS', Converter::toList([
            NotificationChannels::EMAIL,
            NotificationChannels::SLACK,
            NotificationChannels::WEBHOOK,
            NotificationChannels::NEWRELIC,
            NotificationChannels::JIRA,
            NotificationChannels::GITHUB,
          ]));
        },
      ],

      'notification channels - discovery - order' => [
        [],
        [NotificationChannels::id() => [NotificationChannels::EMAIL, NotificationChannels::SLACK]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          // Test that order is normalized (alphabetically sorted).
          $test->stubDotenvValue('VORTEX_NOTIFY_CHANNELS', Converter::toList([NotificationChannels::SLACK, NotificationChannels::EMAIL]));
        },
      ],

      'notification channels - discovery - invalid' => [
        [],
        [NotificationChannels::id() => [NotificationChannels::EMAIL]] + $expected_installed,
        function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
          $test->stubVortexProject($config);
          // No VORTEX_NOTIFY_CHANNELS in .env - should fall back to default.
        },
      ],
    ];
  }

}
