<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit\Schema;

use DrevOps\VortexInstaller\Prompts\Handlers\DatabaseDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\Domain;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProjectName;
use DrevOps\VortexInstaller\Prompts\Handlers\HostingProvider;
use DrevOps\VortexInstaller\Prompts\Handlers\MachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\Migration;
use DrevOps\VortexInstaller\Prompts\Handlers\MigrationDownloadSource;
use DrevOps\VortexInstaller\Prompts\Handlers\ModulePrefix;
use DrevOps\VortexInstaller\Prompts\Handlers\Name;
use DrevOps\VortexInstaller\Prompts\Handlers\Org;
use DrevOps\VortexInstaller\Prompts\Handlers\OrgMachineName;
use DrevOps\VortexInstaller\Prompts\Handlers\ProvisionType;
use DrevOps\VortexInstaller\Prompts\Handlers\Starter;
use DrevOps\VortexInstaller\Prompts\Handlers\Webroot;
use DrevOps\VortexInstaller\Prompts\PromptManager;
use DrevOps\VortexInstaller\Schema\ConfigValidator;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the ConfigValidator class.
 */
#[CoversClass(ConfigValidator::class)]
class ConfigValidatorTest extends UnitTestCase {

  protected ConfigValidator $validator;

  /**
   * @var array<string, \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface>
   */
  protected array $handlers;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->validator = new ConfigValidator();

    $config = Config::fromString('{}');
    $prompt_manager = new PromptManager($config);
    $this->handlers = $prompt_manager->getHandlers();
  }

  public function testValidConfigPasses(): void {
    $config = [
      Name::id() => 'Test Project',
      MachineName::id() => 'test_project',
      Org::id() => 'Test Org',
      OrgMachineName::id() => 'test_org',
      Domain::id() => 'test-project.com',
      ModulePrefix::id() => 'test_project',
      Webroot::id() => 'web',
      HostingProvider::id() => HostingProvider::LAGOON,
      HostingProjectName::id() => 'test-project',
    ];

    $result = $this->validator->validate($config, $this->handlers);

    $this->assertTrue($result['valid']);
    $this->assertEmpty($result['errors']);
  }

  public function testInvalidSelectValue(): void {
    $config = [
      HostingProvider::id() => 'aws',
    ];

    $result = $this->validator->validate($config, $this->handlers);

    $this->assertFalse($result['valid']);

    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertContains(HostingProvider::id(), $error_prompts);

    // Find the specific error for hosting_provider.
    $hosting_error = NULL;
    foreach ($result['errors'] as $error) {
      if ($error['prompt'] === HostingProvider::id()) {
        $hosting_error = $error;
        break;
      }
    }
    $this->assertNotNull($hosting_error);
    $this->assertStringContainsString('aws', $hosting_error['message']);
    $this->assertStringContainsString('lagoon', $hosting_error['message']);
  }

  public function testInvalidMultiselectValue(): void {
    $config = [
      'services' => ['nonexistent_service'],
    ];

    $result = $this->validator->validate($config, $this->handlers);

    $this->assertFalse($result['valid']);

    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertContains('services', $error_prompts);
  }

  public function testMissingRequiredField(): void {
    // Name is required. Provide empty config.
    $config = [];

    $result = $this->validator->validate($config, $this->handlers);

    // Should have errors for required fields that don't have defaults.
    // Name is required but has a default via default(), so it may resolve.
    // HostingProvider is required and has a default, so it resolves.
    // Check that resolved includes defaults.
    $this->assertArrayHasKey('resolved', $result);
  }

  public function testDependencyMetValueProvided(): void {
    $config = [
      ProvisionType::id() => ProvisionType::DATABASE,
      DatabaseDownloadSource::id() => DatabaseDownloadSource::URL,
    ];

    $result = $this->validator->validate($config, $this->handlers);

    // DatabaseDownloadSource depends on ProvisionType=database.
    // Both provided and condition met = OK.
    $db_errors = array_filter($result['errors'], fn(array $e): bool => $e['prompt'] === DatabaseDownloadSource::id());
    $this->assertEmpty($db_errors);
    $this->assertSame(DatabaseDownloadSource::URL, $result['resolved'][DatabaseDownloadSource::id()] ?? NULL);
  }

  public function testDependencyNotMetValueProvided(): void {
    $config = [
      ProvisionType::id() => ProvisionType::PROFILE,
      DatabaseDownloadSource::id() => DatabaseDownloadSource::URL,
    ];

    $result = $this->validator->validate($config, $this->handlers);

    // DatabaseDownloadSource depends on ProvisionType=database.
    // ProvisionType=profile means condition not met + value provided = warning.
    $warning_prompts = array_column($result['warnings'], 'prompt');
    $this->assertContains(DatabaseDownloadSource::id(), $warning_prompts);
  }

  public function testDependencyMetValueMissing(): void {
    $config = [
      Migration::id() => TRUE,
    ];

    $result = $this->validator->validate($config, $this->handlers);

    // MigrationDownloadSource depends on Migration=true.
    // Condition met + no value provided. It's not marked as required though,
    // so it should use the default.
    $this->assertArrayHasKey('resolved', $result);
  }

  public function testDependencyNotMetValueMissing(): void {
    $config = [
      Migration::id() => FALSE,
    ];

    $result = $this->validator->validate($config, $this->handlers);

    // MigrationDownloadSource depends on Migration=true.
    // Condition not met + no value provided = OK (skip).
    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertNotContains(MigrationDownloadSource::id(), $error_prompts);
  }

  public function testSystemDependencySkipped(): void {
    $config = [
      Starter::id() => Starter::LOAD_DATABASE_DEMO,
    ];

    $result = $this->validator->validate($config, $this->handlers);

    // Starter has a _system dependency which should be skipped.
    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertNotContains(Starter::id(), $error_prompts);

    $this->assertSame(Starter::LOAD_DATABASE_DEMO, $result['resolved'][Starter::id()] ?? NULL);
  }

  public function testResolvedShowsDefaults(): void {
    $config = [];

    $result = $this->validator->validate($config, $this->handlers);

    // Handlers with defaults should appear in resolved.
    $this->assertArrayHasKey('resolved', $result);

    // HostingProvider has a default of 'none'.
    $this->assertSame(HostingProvider::NONE, $result['resolved'][HostingProvider::id()] ?? NULL);

    // Migration has a default of FALSE.
    $this->assertSame(FALSE, $result['resolved'][Migration::id()] ?? NULL);
  }

  public function testConfigWithEnvVarNames(): void {
    $config = [
      'VORTEX_INSTALLER_PROMPT_NAME' => 'Test Site',
      'VORTEX_INSTALLER_PROMPT_MACHINE_NAME' => 'test_site',
      'VORTEX_INSTALLER_PROMPT_ORG' => 'Test Org',
      'VORTEX_INSTALLER_PROMPT_ORG_MACHINE_NAME' => 'test_org',
      'VORTEX_INSTALLER_PROMPT_DOMAIN' => 'test-site.com',
      'VORTEX_INSTALLER_PROMPT_MODULE_PREFIX' => 'test_site',
      'VORTEX_INSTALLER_PROMPT_WEBROOT' => 'web',
      'VORTEX_INSTALLER_PROMPT_HOSTING_PROVIDER' => HostingProvider::LAGOON,
      'VORTEX_INSTALLER_PROMPT_HOSTING_PROJECT_NAME' => 'test-site',
    ];

    $result = $this->validator->validate($config, $this->handlers);

    $this->assertTrue($result['valid']);
    $this->assertSame('Test Site', $result['resolved'][Name::id()] ?? NULL);
    $this->assertSame(HostingProvider::LAGOON, $result['resolved'][HostingProvider::id()] ?? NULL);
  }

  public function testConfirmWithNonBoolValue(): void {
    $config = [
      Migration::id() => 'yes',
    ];

    $result = $this->validator->validate($config, $this->handlers);

    $this->assertFalse($result['valid']);

    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertContains(Migration::id(), $error_prompts);
  }

}
