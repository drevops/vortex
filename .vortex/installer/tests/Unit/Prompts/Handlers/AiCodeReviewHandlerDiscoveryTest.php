<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeReview;
use DrevOps\VortexInstaller\Utils\Config;
use DrevOps\VortexInstaller\Utils\File;
use Laravel\Prompts\Key;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(AiCodeReview::class)]
class AiCodeReviewHandlerDiscoveryTest extends AbstractHandlerDiscoveryTestCase {

  public static function dataProviderRunPrompts(): \Iterator {
    $expected_defaults = static::getExpectedDefaults();
    $expected_installed = static::getExpectedInstalled();
    yield 'ai code review - prompt' => [
      [AiCodeReview::id() => Key::ENTER],
      [AiCodeReview::id() => FALSE] + $expected_defaults,
    ];
    yield 'ai code review - prompt - enabled' => [
      [AiCodeReview::id() => Key::LEFT . Key::ENTER],
      [AiCodeReview::id() => TRUE] + $expected_defaults,
    ];
    yield 'ai code review - discovery' => [
      [],
      [AiCodeReview::id() => TRUE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
        File::dump(static::$sut . '/.coderabbit.yaml');
      },
    ];
    yield 'ai code review - discovery - removed' => [
      [],
      [AiCodeReview::id() => FALSE] + $expected_installed,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        $test->stubVortexProject($config);
      },
    ];
    yield 'ai code review - discovery - non-Vortex' => [
      [],
      [AiCodeReview::id() => FALSE] + $expected_defaults,
      function (AbstractHandlerDiscoveryTestCase $test, Config $config): void {
        File::dump(static::$sut . '/.coderabbit.yaml');
      },
    ];
  }

}
