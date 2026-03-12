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

  public static function dataProviderRenderContainsSections(): \Iterator {
    yield 'title' => ['# Vortex Installer - AI Agent Instructions'];
    yield 'workflow section' => ['## Workflow'];
    yield 'commands section' => ['## Commands'];
    yield 'schema format section' => ['## Schema Format'];
    yield 'value types section' => ['## Value Types by Prompt Type'];
    yield 'dependencies section' => ['## Dependencies'];
    yield 'validation output section' => ['## Validation Output'];
    yield 'tips section' => ['## Tips'];
  }

  #[DataProvider('dataProviderRenderContainsCommandExamples')]
  public function testRenderContainsCommandExamples(string $example): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString($example, $result);
  }

  public static function dataProviderRenderContainsCommandExamples(): \Iterator {
    yield 'schema flag' => ['--schema'];
    yield 'validate flag' => ['--validate'];
    yield 'no-interaction flag' => ['--no-interaction'];
    yield 'config flag' => ['--config'];
    yield 'destination flag' => ['--destination'];
  }

  #[DataProvider('dataProviderRenderContainsPromptTypes')]
  public function testRenderContainsPromptTypes(string $type): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString('`' . $type . '`', $result);
  }

  public static function dataProviderRenderContainsPromptTypes(): \Iterator {
    yield 'text' => ['text'];
    yield 'select' => ['select'];
    yield 'multiselect' => ['multiselect'];
    yield 'confirm' => ['confirm'];
    yield 'suggest' => ['suggest'];
  }

  #[DataProvider('dataProviderRenderContainsSchemaFields')]
  public function testRenderContainsSchemaFields(string $field): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString('`' . $field . '`', $result);
  }

  public static function dataProviderRenderContainsSchemaFields(): \Iterator {
    yield 'id field' => ['id'];
    yield 'env field' => ['env'];
    yield 'type field' => ['type'];
    yield 'label field' => ['label'];
    yield 'options field' => ['options'];
    yield 'default field' => ['default'];
    yield 'required field' => ['required'];
    yield 'depends_on field' => ['depends_on'];
  }

  #[DataProvider('dataProviderRenderContainsValidationFields')]
  public function testRenderContainsValidationFields(string $field): void {
    $result = AgentHelp::render();

    $this->assertStringContainsString('`' . $field . '`', $result);
  }

  public static function dataProviderRenderContainsValidationFields(): \Iterator {
    yield 'valid' => ['valid'];
    yield 'errors' => ['errors'];
    yield 'warnings' => ['warnings'];
    yield 'resolved' => ['resolved'];
  }

  public function testRenderIsIdempotent(): void {
    $first = AgentHelp::render();
    $second = AgentHelp::render();

    $this->assertSame($first, $second);
  }

}
