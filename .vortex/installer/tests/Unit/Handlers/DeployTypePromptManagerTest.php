<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\DeployType;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\Converter;
use PHPUnit\Framework\Attributes\CoversClass;
use Laravel\Prompts\Key;

#[CoversClass(DeployType::class)]
class DeployTypePromptManagerTest extends AbstractPromptManagerTestCase {

  public static function dataProviderRunPrompts(): array {
    $expected_defaults = static::getExpectedDefaults();

    return [
      'deploy type - prompt' => [
        [DeployType::id() => Key::ENTER],
        [DeployType::id() => [DeployType::WEBHOOK]] + $expected_defaults,
      ],

      'deploy type - discovery' => [
        [],
        [DeployType::id() => [DeployType::ARTIFACT, DeployType::WEBHOOK]] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_DEPLOY_TYPES', Converter::toList([DeployType::ARTIFACT, DeployType::WEBHOOK]));
        },
      ],

      'deploy type - discovery - order' => [
        [],
        [DeployType::id() => [DeployType::ARTIFACT, DeployType::WEBHOOK]] + $expected_defaults,
        function (AbstractPromptManagerTestCase $test, Config $config): void {
          $test->stubDotenvValue('VORTEX_DEPLOY_TYPES', Converter::toList([DeployType::WEBHOOK, DeployType::ARTIFACT]));
        },
      ],

      'deploy type - discovery - invalid' => [
        [],
        $expected_defaults,
        function (AbstractPromptManagerTestCase $test): void {
          // No VORTEX_DEPLOY_TYPES in .env - should fall back to default.
        },
      ],
    ];
  }

}
