<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts;

use DrevOps\VortexInstaller\Prompts\PromptType;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the PromptType enum.
 */
#[CoversClass(PromptType::class)]
class PromptTypeTest extends UnitTestCase {

  #[DataProvider('dataProviderAllCasesHavePromptFunction')]
  public function testAllCasesHavePromptFunction(PromptType $case): void {
    $this->assertNotEmpty($case->promptFunction());
  }

  /**
   * Data provider for testAllCasesHavePromptFunction.
   */
  public static function dataProviderAllCasesHavePromptFunction(): \Iterator {
    foreach (PromptType::cases() as $case) {
      yield $case->name => [$case];
    }
  }

  #[DataProvider('dataProviderPromptFunctionMatchesCaseValue')]
  public function testPromptFunctionMatchesCaseValue(PromptType $case): void {
    $this->assertSame($case->value, $case->promptFunction());
  }

  /**
   * Data provider for testPromptFunctionMatchesCaseValue.
   */
  public static function dataProviderPromptFunctionMatchesCaseValue(): \Iterator {
    foreach (PromptType::cases() as $case) {
      yield $case->name => [$case];
    }
  }

  #[DataProvider('dataProviderFromValidString')]
  public function testFromValidString(string $value, PromptType $expected): void {
    $this->assertSame($expected, PromptType::from($value));
  }

  /**
   * Data provider for testFromValidString.
   */
  public static function dataProviderFromValidString(): \Iterator {
    yield 'text' => ['text', PromptType::Text];
    yield 'select' => ['select', PromptType::Select];
    yield 'multiselect' => ['multiselect', PromptType::MultiSelect];
    yield 'confirm' => ['confirm', PromptType::Confirm];
    yield 'suggest' => ['suggest', PromptType::Suggest];
    yield 'number' => ['number', PromptType::Number];
    yield 'textarea' => ['textarea', PromptType::Textarea];
    yield 'password' => ['password', PromptType::Password];
    yield 'search' => ['search', PromptType::Search];
    yield 'multisearch' => ['multisearch', PromptType::MultiSearch];
    yield 'pause' => ['pause', PromptType::Pause];
  }

  public function testFromInvalidString(): void {
    $this->assertNull(PromptType::tryFrom('invalid'));
    $this->assertNull(PromptType::tryFrom(''));
    $this->assertNull(PromptType::tryFrom('TEXT'));
  }

}
