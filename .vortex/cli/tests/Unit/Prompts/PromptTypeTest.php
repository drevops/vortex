<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Unit\Prompts;

use DrevOps\VortexCli\Prompts\PromptType;
use DrevOps\VortexCli\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the PromptType enum.
 */
#[CoversClass(PromptType::class)]
class PromptTypeTest extends UnitTestCase {

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
