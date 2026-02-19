<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Schema;

use DrevOps\VortexInstaller\Prompts\Handlers\AiCodeInstructions;
use DrevOps\VortexInstaller\Prompts\Handlers\CiProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseImage;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\ProfileCustom;
use DrevOps\VortexInstaller\Prompts\Handlers\ThemeCustom;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Schema\SchemaGenerator;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the SchemaGenerator class.
 */
#[CoversClass(SchemaGenerator::class)]
class SchemaGeneratorTest extends UnitTestCase {

  /**
   * Schema result cache.
   */
  protected static ?array $schema = NULL;

  /**
   * Handler instances cache.
   */
  protected static ?array $handlers = NULL;

  /**
   * Get the generated schema (cached).
   */
  protected function getSchema(): array {
    if (static::$schema === NULL) {
      $config = Config::fromString('{}');
      $prompt_manager = new PromptManager($config);
      static::$handlers = $prompt_manager->getHandlers();

      $generator = new SchemaGenerator();
      static::$schema = $generator->generate(static::$handlers);
    }

    return static::$schema;
  }

  public function testGenerateSchema(): void {
    $schema = $this->getSchema();

    $this->assertArrayHasKey('prompts', $schema);
    $this->assertIsArray($schema['prompts']);
    $this->assertNotEmpty($schema['prompts']);
  }

  public function testAllPromptsHaveRequiredFields(): void {
    $schema = $this->getSchema();

    $required_fields = ['id', 'env', 'type', 'label', 'required'];

    foreach ($schema['prompts'] as $index => $prompt) {
      foreach ($required_fields as $required_field) {
        $this->assertArrayHasKey($required_field, $prompt, sprintf('Prompt at index %d missing field "%s".', $index, $required_field));
      }
    }
  }

  #[DataProvider('dataProviderPromptTypes')]
  public function testPromptTypes(string $handler_id, string $expected_type): void {
    $schema = $this->getSchema();

    $prompt = $this->findPromptById($schema, $handler_id);
    $this->assertNotNull($prompt, sprintf('Prompt "%s" not found in schema.', $handler_id));
    $this->assertSame($expected_type, $prompt['type'], sprintf('Prompt "%s" has wrong type.', $handler_id));
  }

  /**
   * Data provider for testPromptTypes.
   */
  public static function dataProviderPromptTypes(): array {
    return [
      'name' => [Name::id(), 'text'],
      'hosting_provider' => [HostingProvider::id(), 'select'],
      'ci_provider' => [CiProvider::id(), 'select'],
      'migration' => [Migration::id(), 'confirm'],
      'database_image' => [DatabaseImage::id(), 'text'],
      'ai_code_instructions' => [AiCodeInstructions::id(), 'confirm'],
    ];
  }

  public function testOptionsFormat(): void {
    $schema = $this->getSchema();

    foreach ($schema['prompts'] as $prompt) {
      if ($prompt['options'] === NULL) {
        continue;
      }

      $this->assertIsArray($prompt['options'], sprintf('Options for "%s" should be array.', $prompt['id']));

      foreach ($prompt['options'] as $option) {
        $this->assertArrayHasKey('value', $option, sprintf('Option in "%s" missing "value" key.', $prompt['id']));
        $this->assertArrayHasKey('label', $option, sprintf('Option in "%s" missing "label" key.', $prompt['id']));
      }
    }
  }

  #[DataProvider('dataProviderDependsOnFormat')]
  public function testDependsOnFormat(string $handler_id): void {
    $schema = $this->getSchema();

    $prompt = $this->findPromptById($schema, $handler_id);
    $this->assertNotNull($prompt, sprintf('Prompt "%s" not found.', $handler_id));
    $this->assertNotNull($prompt['depends_on'], sprintf('Prompt "%s" should have depends_on.', $handler_id));
    $this->assertIsArray($prompt['depends_on']);
  }

  /**
   * Data provider for testDependsOnFormat.
   */
  public static function dataProviderDependsOnFormat(): array {
    return [
      'hosting_project_name' => [HostingProjectName::id()],
      'profile_custom' => [ProfileCustom::id()],
      'theme_custom' => [ThemeCustom::id()],
      'database_download_source' => [DatabaseDownloadSource::id()],
      'database_image' => [DatabaseImage::id()],
      'migration_download_source' => [MigrationDownloadSource::id()],
    ];
  }

  public function testUtilityHandlersExcluded(): void {
    $schema = $this->getSchema();

    $ids = array_column($schema['prompts'], 'id');

    $this->assertNotContains('dotenv', $ids, 'Dotenv handler should be excluded.');
    $this->assertNotContains('internal', $ids, 'Internal handler should be excluded.');
  }

  public function testEnvNameFormat(): void {
    $schema = $this->getSchema();

    foreach ($schema['prompts'] as $prompt) {
      $this->assertMatchesRegularExpression('/^VORTEX_INSTALLER_PROMPT_[A-Z0-9_]+$/', $prompt['env'], sprintf('Env name for "%s" does not match expected format.', $prompt['id']));
    }
  }

  public function testSchemaStaysInSync(): void {
    $schema = $this->getSchema();

    $excluded = SchemaGenerator::getExcludedHandlers();
    $expected_count = count(array_filter(array_keys(static::$handlers), fn(string $id): bool => !in_array($id, $excluded, TRUE)));

    $this->assertCount($expected_count, $schema['prompts'], 'Schema prompt count should match handlers minus excluded.');
  }

  /**
   * Find a prompt in schema by its ID.
   */
  protected function findPromptById(array $schema, string $id): ?array {
    foreach ($schema['prompts'] as $prompt) {
      if ($prompt['id'] === $id) {
        return $prompt;
      }
    }

    return NULL;
  }

}
