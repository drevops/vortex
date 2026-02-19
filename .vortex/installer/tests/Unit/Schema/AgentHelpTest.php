<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Schema;

use DrevOps\VortexInstaller\Schema\AgentHelp;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the AgentHelp class.
 */
#[CoversClass(AgentHelp::class)]
class AgentHelpTest extends UnitTestCase {

  public function testRenderReturnsNonEmptyString(): void {
    $result = AgentHelp::render();

    $this->assertNotEmpty($result);
  }

  #[DataProvider('dataProviderRenderContainsSections')]
  public function testRenderContainsSections(string $section): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString($section, $result);
  }

  public static function dataProviderRenderContainsSections(): array {
    return [
      'title' => ['# Vortex Installer - AI Agent Instructions'],
      'workflow section' => ['## Workflow'],
      'commands section' => ['## Commands'],
      'schema format section' => ['## Schema Format'],
      'value types section' => ['## Value Types by Prompt Type'],
      'dependencies section' => ['## Dependencies'],
      'validation output section' => ['## Validation Output'],
      'tips section' => ['## Tips'],
    ];
  }

  #[DataProvider('dataProviderRenderContainsCommandExamples')]
  public function testRenderContainsCommandExamples(string $example): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString($example, $result);
  }

  public static function dataProviderRenderContainsCommandExamples(): array {
    return [
      'schema flag' => ['--schema'],
      'validate flag' => ['--validate'],
      'no-interaction flag' => ['--no-interaction'],
      'config flag' => ['--config'],
      'destination flag' => ['--destination'],
    ];
  }

  #[DataProvider('dataProviderRenderContainsPromptTypes')]
  public function testRenderContainsPromptTypes(string $type): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString('`' . $type . '`', $result);
  }

  public static function dataProviderRenderContainsPromptTypes(): array {
    return [
      'text' => ['text'],
      'select' => ['select'],
      'multiselect' => ['multiselect'],
      'confirm' => ['confirm'],
      'suggest' => ['suggest'],
    ];
  }

  #[DataProvider('dataProviderRenderContainsSchemaFields')]
  public function testRenderContainsSchemaFields(string $field): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString('`' . $field . '`', $result);
  }

  public static function dataProviderRenderContainsSchemaFields(): array {
    return [
      'id field' => ['id'],
      'env field' => ['env'],
      'type field' => ['type'],
      'label field' => ['label'],
      'options field' => ['options'],
      'default field' => ['default'],
      'required field' => ['required'],
      'depends_on field' => ['depends_on'],
    ];
  }

  #[DataProvider('dataProviderRenderContainsValidationFields')]
  public function testRenderContainsValidationFields(string $field): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString('`' . $field . '`', $result);
  }

  public static function dataProviderRenderContainsValidationFields(): array {
    return [
      'valid' => ['valid'],
      'errors' => ['errors'],
      'warnings' => ['warnings'],
      'resolved' => ['resolved'],
    ];
  }

  public function testRenderIsIdempotent(): void {
    $first = AgentHelp::render();
    $second = AgentHelp::render();

    $this->assertSame($first, $second);
  }

}
