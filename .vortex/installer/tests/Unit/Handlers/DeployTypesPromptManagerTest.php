<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DeployTypes;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(DeployTypes::class)]
class DeployTypesPromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'deploy types - prompt' => [
        [DeployTypes::id() => Key::ENTER],
        [DeployTypes::id() => [DeployTypes::WEBHOOK]] + $expected_defaults,
      ],

      'deploy types - discovery' => [
        [],
        [DeployTypes::id() => [DeployTypes::ARTIFACT, DeployTypes::WEBHOOK]] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_DEPLOY_TYPES', Converter::toList([DeployTypes::ARTIFACT, DeployTypes::WEBHOOK]));
        },
      ],

      'deploy types - discovery - order' => [
        [],
        [DeployTypes::id() => [DeployTypes::ARTIFACT, DeployTypes::WEBHOOK]] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_DEPLOY_TYPES', Converter::toList([DeployTypes::WEBHOOK, DeployTypes::ARTIFACT]));
        },
      ],

      'deploy types - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No VORTEX_DEPLOY_TYPES in .env - should fall back to default.
        },
      ],
    ];
  }

}
