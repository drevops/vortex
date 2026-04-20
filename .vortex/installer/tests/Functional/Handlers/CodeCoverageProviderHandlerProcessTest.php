<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Handlers;

use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\CodeCoverageProvider;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CodeCoverageProvider::class)]
class CodeCoverageProviderHandlerProcessTest extends AbstractHandlerProcessTestCase {

  public static function dataProviderHandlerProcess(): \Iterator {
    yield 'code_coverage_provider_codecov' => [
      static::cw(fn($test): string => $test->prompts[CodeCoverageProvider::id()] = CodeCoverageProvider::CODECOV),
    ];
    yield 'code_coverage_provider_codecov_circleci' => [
      static::cw(function ($test): void {
          $test->prompts[CodeCoverageProvider::id()] = CodeCoverageProvider::CODECOV;
          $test->prompts[CiProvider::id()] = CiProvider::CIRCLECI;
      }),
    ];
  }

}
