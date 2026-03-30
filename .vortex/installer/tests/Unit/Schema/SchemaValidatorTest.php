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
use DrevOps\VortexInstaller\Schema\SchemaValidator;
use DrevOps\VortexInstaller\Tests\Unit\UnitTestCase;
use DrevOps\VortexInstaller\Utils\Config;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the SchemaValidator class.
 */
#[CoversClass(SchemaValidator::class)]
class SchemaValidatorTest extends UnitTestCase {

  protected SchemaValidator $validator;

  /**
   * @var array<string, \DrevOps\VortexInstaller\Prompts\Handlers\HandlerInterface>
   */
  protected array $handlers;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = Config::fromString('{}');
    $prompt_manager = new PromptManager($config);
    $this->handlers = $prompt_manager->getHandlers();
    $this->validator = new SchemaValidator($this->handlers);
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

    $result = $this->validator->validate($config);

    $this->assertTrue($result['valid']);
    $this->assertEmpty($result['errors']);
  }

  public function testInvalidSelectValue(): void {
    $config = [
      HostingProvider::id() => 'aws',
    ];

    $result = $this->validator->validate($config);

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

    $result = $this->validator->validate($config);

    $this->assertFalse($result['valid']);

    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertContains('services', $error_prompts);
  }

  public function testEmptyConfigIsValid(): void {
    $config = [];

    $result = $this->validator->validate($config);

    // Empty config is valid — prompts not provided are skipped.
    $this->assertTrue($result['valid']);
    $this->assertEmpty($result['errors']);
    $this->assertEmpty($result['resolved']);
  }

  public function testDependencyMetValueProvided(): void {
    $config = [
      ProvisionType::id() => ProvisionType::DATABASE,
      DatabaseDownloadSource::id() => DatabaseDownloadSource::URL,
    ];

    $result = $this->validator->validate($config);

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

    $result = $this->validator->validate($config);

    // DatabaseDownloadSource depends on ProvisionType=database.
    // ProvisionType=profile means condition not met + value provided = warning.
    $warning_prompts = array_column($result['warnings'], 'prompt');
    $this->assertContains(DatabaseDownloadSource::id(), $warning_prompts);
  }

  public function testDependencyMetValueMissing(): void {
    $config = [
      Migration::id() => TRUE,
    ];

    $result = $this->validator->validate($config);

    // MigrationDownloadSource depends on Migration=true.
    // Condition met + no value provided + not required = OK (skip).
    $this->assertTrue($result['valid']);
    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertNotContains(MigrationDownloadSource::id(), $error_prompts);
  }

  public function testDependencyNotMetValueMissing(): void {
    $config = [
      Migration::id() => FALSE,
    ];

    $result = $this->validator->validate($config);

    // MigrationDownloadSource depends on Migration=true.
    // Condition not met + no value provided = OK (skip).
    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertNotContains(MigrationDownloadSource::id(), $error_prompts);
  }

  public function testSystemDependencySkipped(): void {
    $config = [
      Starter::id() => Starter::LOAD_DATABASE_DEMO,
    ];

    $result = $this->validator->validate($config);

    // Starter has a _system dependency which should be skipped.
    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertNotContains(Starter::id(), $error_prompts);

    $this->assertSame(Starter::LOAD_DATABASE_DEMO, $result['resolved'][Starter::id()] ?? NULL);
  }

  public function testResolvedOnlyContainsProvidedValues(): void {
    $config = [
      HostingProvider::id() => HostingProvider::LAGOON,
      HostingProjectName::id() => 'test-project',
    ];

    $result = $this->validator->validate($config);

    $this->assertTrue($result['valid']);
    $this->assertSame(HostingProvider::LAGOON, $result['resolved'][HostingProvider::id()]);
    $this->assertSame('test-project', $result['resolved'][HostingProjectName::id()]);
    // Unprovided prompts should not appear in resolved.
    $this->assertArrayNotHasKey(Migration::id(), $result['resolved']);
  }

  public function testConfirmWithNonBoolValue(): void {
    $config = [
      Migration::id() => 'yes',
    ];

    $result = $this->validator->validate($config);

    $this->assertFalse($result['valid']);

    $error_prompts = array_column($result['errors'], 'prompt');
    $this->assertContains(Migration::id(), $error_prompts);
  }

}
