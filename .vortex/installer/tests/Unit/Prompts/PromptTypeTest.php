<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Prompts;

use AlexSkrypnyk\Prompty\Prompty;
use DrevOps\VortexInstaller\Prompts\PromptType;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the PromptType enum.
 */
#[CoversClass(PromptType::class)]
class PromptTypeTest extends UnitTestCase {

  #[DataProvider('dataProviderPromptFunctionResolvesToWidget')]
  public function testPromptFunctionResolvesToWidget(PromptType $case): void {
    $reflection = new \ReflectionFunction($case->promptFunction());

    $this->assertSame(Prompty::class, $reflection->getClosureScopeClass()?->getName());
    $this->assertSame($case->value, $reflection->getName());
  }

  /**
   * Data provider for testPromptFunctionResolvesToWidget.
   */
  public static function dataProviderPromptFunctionResolvesToWidget(): \Iterator {
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
  }

  public function testFromInvalidString(): void {
    $this->assertNull(PromptType::tryFrom('invalid'));
    $this->assertNull(PromptType::tryFrom(''));
    $this->assertNull(PromptType::tryFrom('TEXT'));
  }

}
