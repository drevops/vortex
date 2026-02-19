<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Functional\Command;

use DrevOps\VortexInstaller\Command\InstallCommand;
use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Schema\ConfigValidator;
use DrevOps\VortexInstaller\Schema\SchemaGenerator;
use DrevOps\VortexInstaller\Tests\Functional\FunctionalTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Functional tests for --schema, --validate, and --agent-help options.
 */
#[CoversClass(InstallCommand::class)]
#[CoversClass(SchemaGenerator::class)]
#[CoversClass(ConfigValidator::class)]
class SchemaValidateCommandTest extends FunctionalTestCase {

  /**
   * Path to schema fixture files.
   */
  protected static string $schemaFixturesDir;

  protected function setUp(): void {
    parent::setUp();

    static::$schemaFixturesDir = dirname(__DIR__, 2) . '/Fixtures/schema';
    static::applicationInitFromCommand(InstallCommand::class);
  }

  // -------------------------------------------------------------------------
  // --schema tests.
  // -------------------------------------------------------------------------

  /**
   * Test --schema outputs valid JSON with expected structure.
   */
  public function testSchemaOutputIsValidJson(): void {
    $this->applicationRun([
      '--' . InstallCommand::OPTION_SCHEMA => TRUE,
    ]);

    $output = $this->applicationGetOutput();
    $schema = json_decode($output, TRUE);

    $this->assertIsArray($schema);
    $this->assertArrayHasKey('prompts', $schema);
    $this->assertNotEmpty($schema['prompts']);
  }

  /**
   * Test --schema contains expected prompt fields.
   */
  public function testSchemaPromptsHaveRequiredFields(): void {
    $this->applicationRun([
      '--' . InstallCommand::OPTION_SCHEMA => TRUE,
    ]);

    $schema = json_decode($this->applicationGetOutput(), TRUE);
    $required_fields = ['id', 'env', 'type', 'label', 'required'];

    foreach ($schema['prompts'] as $prompt) {
      foreach ($required_fields as $required_field) {
        $this->assertArrayHasKey($required_field, $prompt, sprintf('Prompt "%s" missing field "%s".', $prompt['id'] ?? '?', $required_field));
      }
    }
  }

  /**
   * Test --schema excludes utility handlers.
   */
  public function testSchemaExcludesUtilityHandlers(): void {
    $this->applicationRun([
      '--' . InstallCommand::OPTION_SCHEMA => TRUE,
    ]);

    $schema = json_decode($this->applicationGetOutput(), TRUE);
    $ids = array_column($schema['prompts'], 'id');

    $this->assertNotContains('dotenv', $ids);
    $this->assertNotContains('internal', $ids);
  }

  /**
   * Test --schema select prompts have options with value and label.
   */
  public function testSchemaSelectOptionsFormat(): void {
    $this->applicationRun([
      '--' . InstallCommand::OPTION_SCHEMA => TRUE,
    ]);

    $schema = json_decode($this->applicationGetOutput(), TRUE);

    foreach ($schema['prompts'] as $prompt) {
      if ($prompt['type'] !== 'select' && $prompt['type'] !== 'multiselect') {
        continue;
      }

      $this->assertNotNull($prompt['options'], sprintf('Select prompt "%s" should have options.', $prompt['id']));

      foreach ($prompt['options'] as $option) {
        $this->assertArrayHasKey('value', $option, sprintf('Option in "%s" missing "value".', $prompt['id']));
        $this->assertArrayHasKey('label', $option, sprintf('Option in "%s" missing "label".', $prompt['id']));
      }
    }
  }

  // -------------------------------------------------------------------------
  // --validate tests.
  // -------------------------------------------------------------------------

  /**
   * Test --validate with various config inputs.
   *
   * Expectations array supports the following keys:
   * - 'valid' (bool): Expected validity of the config.
   * - 'output_contains' (string): Substring expected in raw output (for
   *   non-JSON responses like broken JSON or missing --config).
   * - 'error_prompt' (string): Expected prompt ID in errors.
   * - 'error_message' (string): Expected substring in the error message.
   * - 'warning_prompt' (string): Expected prompt ID in warnings.
   * - 'resolved' (array): Key-value pairs expected in resolved config.
   */
  #[DataProvider('dataProviderValidate')]
  public function testValidate(?string $config, array $expectations): void {
    $options = [
      '--' . InstallCommand::OPTION_VALIDATE => TRUE,
    ];

    if ($config !== NULL) {
      // Resolve fixture files relative to the fixtures directory.
      $path = str_starts_with($config, '/') ? $config : static::$schemaFixturesDir . '/' . $config;
      $options['--' . InstallCommand::OPTION_CONFIG] = $path;
    }

    $expect_failure = isset($expectations['valid']) ? !$expectations['valid'] : TRUE;
    $this->applicationRun($options, [], $expect_failure);

    $output = $this->applicationGetOutput();

    // Raw output assertion (for non-JSON responses).
    if (isset($expectations['output_contains'])) {
      $this->assertStringContainsString($expectations['output_contains'], $output);
    }

    // JSON result assertions.
    if (isset($expectations['valid'])) {
      $result = json_decode($output, TRUE);
      $this->assertIsArray($result);
      $this->assertSame($expectations['valid'], $result['valid'], sprintf('Errors: %s', json_encode($result['errors'] ?? [])));
      $this->assertArrayHasKey('resolved', $result);

      if ($expectations['valid']) {
        $this->assertEmpty($result['errors']);
      }
      else {
        $this->assertNotEmpty($result['errors']);
      }

      if (isset($expectations['error_prompt'])) {
        $error_prompts = array_column($result['errors'], 'prompt');
        $this->assertContains($expectations['error_prompt'], $error_prompts, sprintf('Errors: %s', json_encode($result['errors'])));

        if (isset($expectations['error_message'])) {
          $error = NULL;
          foreach ($result['errors'] as $e) {
            if ($e['prompt'] === $expectations['error_prompt']) {
              $error = $e;
              break;
            }
          }
          $this->assertNotNull($error);
          $this->assertStringContainsString($expectations['error_message'], $error['message']);
        }
      }

      if (isset($expectations['warning_prompt'])) {
        $this->assertNotEmpty($result['warnings']);
        $warning_prompts = array_column($result['warnings'], 'prompt');
        $this->assertContains($expectations['warning_prompt'], $warning_prompts);
      }

      if (isset($expectations['resolved'])) {
        foreach ($expectations['resolved'] as $key => $value) {
          $this->assertSame($value, $result['resolved'][$key] ?? NULL, sprintf('Resolved key "%s" mismatch.', $key));
        }
      }
    }
  }

  /**
   * Data provider for testValidate.
   */
  public static function dataProviderValidate(): array {
    return [
      // Valid configs.
      'full config with lagoon hosting' => ['valid_full.json', ['valid' => TRUE]],
      'minimal config with defaults' => [
        'valid_minimal.json',
        [
          'valid' => TRUE,
          'resolved' => [
            HostingProvider::id() => HostingProvider::NONE,
            Migration::id() => FALSE,
            Name::id() => 'Minimal Site',
          ],
        ],
      ],
      'config using env var key names' => ['valid_env_keys.json', ['valid' => TRUE]],
      'database provision with container registry' => ['valid_database_provision.json', ['valid' => TRUE]],
      'migration enabled' => ['valid_migration.json', ['valid' => TRUE]],
      'acquia hosting' => ['valid_acquia.json', ['valid' => TRUE]],

      // Invalid configs.
      'invalid hosting provider value' => [
        'invalid_hosting_provider.json',
        ['valid' => FALSE, 'error_prompt' => HostingProvider::id(), 'error_message' => 'aws'],
      ],
      'non-boolean for confirm field' => [
        'invalid_confirm_not_boolean.json',
        ['valid' => FALSE, 'error_prompt' => Migration::id(), 'error_message' => 'Expected boolean'],
      ],
      'invalid database download source' => [
        'invalid_database_source.json',
        ['valid' => FALSE, 'error_prompt' => DatabaseDownloadSource::id(), 'error_message' => 'dropbox'],
      ],
      'invalid provision type' => [
        'invalid_provision_type.json',
        ['valid' => FALSE, 'error_prompt' => 'provision_type', 'error_message' => 'magic'],
      ],

      // Warnings.
      'warning for unmet dependency' => [
        'warning_unmet_dependency.json',
        ['valid' => TRUE, 'warning_prompt' => HostingProjectName::id()],
      ],

      // Empty and array configs.
      'empty JSON object uses defaults' => [
        'empty.json',
        [
          'valid' => FALSE,
          'resolved' => [HostingProvider::id() => HostingProvider::NONE],
        ],
      ],
      'JSON array treated as empty config' => [
        'json_array.json',
        ['valid' => FALSE],
      ],

      // Broken/unparseable inputs.
      'broken JSON syntax' => ['broken_json.json', ['output_contains' => 'Invalid JSON']],
      'plain text file' => ['not_json.txt', ['output_contains' => 'Invalid JSON']],
      'empty file' => ['empty_file.txt', ['output_contains' => 'Invalid JSON']],
      'JSON string instead of object' => ['json_string.json', ['output_contains' => 'Invalid JSON']],
      'non-existent file treated as raw JSON' => ['/nonexistent/path/config.json', ['output_contains' => 'Invalid JSON']],

      // Missing --config.
      'validate without --config fails' => [NULL, ['output_contains' => '--config']],
    ];
  }

  // -------------------------------------------------------------------------
  // --agent-help tests.
  // -------------------------------------------------------------------------

  /**
   * Test --agent-help outputs instructions.
   */
  public function testAgentHelpOutput(): void {
    $this->applicationRun([
      '--' . InstallCommand::OPTION_AGENT_HELP => TRUE,
    ]);

    $output = $this->applicationGetOutput();

    $this->assertStringContainsString('AI Agent Instructions', $output);
    $this->assertStringContainsString('--schema', $output);
    $this->assertStringContainsString('--validate', $output);
    $this->assertStringContainsString('--no-interaction', $output);
    $this->assertStringContainsString('depends_on', $output);
  }

  /**
   * Get the application output as a string.
   */
  protected function applicationGetOutput(): string {
    return $this->applicationGetTester()->getDisplay();
  }

}
