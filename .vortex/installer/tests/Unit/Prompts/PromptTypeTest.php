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
  public static function dataProviderAllCasesHavePromptFunction(): array {
    $cases = [];
    foreach (PromptType::cases() as $case) {
      $cases[$case->name] = [$case];
    }

    return $cases;
  }

  #[DataProvider('dataProviderPromptFunctionMatchesCaseValue')]
  public function testPromptFunctionMatchesCaseValue(PromptType $case): void {
    $this->assertSame($case->value, $case->promptFunction());
  }

  /**
   * Data provider for testPromptFunctionMatchesCaseValue.
   */
  public static function dataProviderPromptFunctionMatchesCaseValue(): array {
    $cases = [];
    foreach (PromptType::cases() as $case) {
      $cases[$case->name] = [$case];
    }

    return $cases;
  }

  #[DataProvider('dataProviderFromValidString')]
  public function testFromValidString(string $value, PromptType $expected): void {
    $this->assertSame($expected, PromptType::from($value));
  }

  /**
   * Data provider for testFromValidString.
   */
  public static function dataProviderFromValidString(): array {
    return [
      'text' => ['text', PromptType::Text],
      'select' => ['select', PromptType::Select],
      'multiselect' => ['multiselect', PromptType::MultiSelect],
      'confirm' => ['confirm', PromptType::Confirm],
      'suggest' => ['suggest', PromptType::Suggest],
      'number' => ['number', PromptType::Number],
      'textarea' => ['textarea', PromptType::Textarea],
      'password' => ['password', PromptType::Password],
      'search' => ['search', PromptType::Search],
      'multisearch' => ['multisearch', PromptType::MultiSearch],
      'pause' => ['pause', PromptType::Pause],
    ];
  }

  public function testFromInvalidString(): void {
    $this->assertNull(PromptType::tryFrom('invalid'));
    $this->assertNull(PromptType::tryFrom(''));
    $this->assertNull(PromptType::tryFrom('TEXT'));
  }

}
