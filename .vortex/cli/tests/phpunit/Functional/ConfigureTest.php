<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Tests\Functional;

use DrevOps\VortexCli\Command\ConfigureCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests the Configure command end to end.
 */
#[CoversClass(ConfigureCommand::class)]
#[Group('command')]
final class ConfigureTest extends TestCase {

  public function testNonInteractive(): void {
    $data = $this->collect(['--prompts' => '{"name":"Acme Site","profile":"minimal"}']);

    $this->assertSame('Acme Site', $data['name']);
    $this->assertSame('acme_site', $data['machine_name']);
    $this->assertSame('Acme Site Org', $data['org']);
    $this->assertSame('acme_site_org', $data['org_machine_name']);
    $this->assertSame('acme-site.com', $data['domain']);
    $this->assertSame('minimal', $data['profile']);
  }

  public function testDerivesAndDefaults(): void {
    $data = $this->collect(['--prompts' => '{"name":"  My Awesome Site  "}']);

    $this->assertSame('My Awesome Site', $data['name']);
    $this->assertSame('my_awesome_site', $data['machine_name']);
    $this->assertSame('mas', $data['module_prefix']);
    $this->assertSame('standard', $data['profile']);
  }

  public function testCollectsEverySection(): void {
    $data = $this->collect(['--prompts' => '{"name":"Acme"}']);

    foreach (['domain', 'starter', 'modules', 'theme', 'code_provider', 'version_scheme', 'timezone', 'services', 'tools', 'hosting_provider', 'webroot', 'deploy_types', 'provision_type', 'notification_channels', 'ci_provider', 'dependency_updates_provider', 'preserve_docs_project', 'ai_code_instructions'] as $id) {
      $this->assertArrayHasKey($id, $data, sprintf('Expected question "%s" in the collected set.', $id));
    }
  }

  public function testConditionalsGateByDefault(): void {
    $data = $this->collect(['--prompts' => '{"name":"Acme"}']);

    $this->assertArrayNotHasKey('profile_custom', $data);
    $this->assertArrayNotHasKey('database_image', $data);
    $this->assertArrayNotHasKey('hosting_project_name', $data);
    $this->assertArrayNotHasKey('migration_fetch_source', $data);
    $this->assertArrayNotHasKey('migration_image', $data);
  }

  public function testProfileCustomActivates(): void {
    $data = $this->collect(['--prompts' => '{"name":"Acme","profile":"custom","profile_custom":"my_profile"}']);

    $this->assertSame('my_profile', $data['profile_custom']);
  }

  public function testMigrationActivatesSource(): void {
    $data = $this->collect(['--prompts' => '{"name":"Acme","migration":true}']);

    $this->assertArrayHasKey('migration_fetch_source', $data);
    $this->assertSame('url', $data['migration_fetch_source']);
  }

  public function testHostingActivatesProjectName(): void {
    $data = $this->collect(['--prompts' => '{"name":"Acme","hosting_provider":"lagoon"}']);

    $this->assertSame('acme', $data['hosting_project_name']);
  }

  public function testRequiredNameFails(): void {
    $tester = $this->tester();

    $exit = $tester->execute(['--prompts' => '{"name":""}'], ['interactive' => FALSE]);

    $this->assertSame(Command::FAILURE, $exit);
    $this->assertStringContainsString('site name is required', $tester->getDisplay());
  }

  public function testInvalidMachineNameFails(): void {
    $tester = $this->tester();

    $exit = $tester->execute(['--prompts' => '{"name":"Acme","machine_name":"Bad Name"}'], ['interactive' => FALSE]);

    $this->assertSame(Command::FAILURE, $exit);
    $this->assertStringContainsString('valid machine name', $tester->getDisplay());
  }

  public function testResolvesDirectoryForDefaultName(): void {
    // A "--dir" ending in "/." must resolve to an absolute path so the default
    // site name derives from the real directory basename, not from ".".
    $tester = $this->tester();

    $exit = $tester->execute(['--prompts' => '{}', '--dir' => dirname(__DIR__, 3) . '/.'], ['interactive' => FALSE]);

    $this->assertSame(Command::SUCCESS, $exit);
    $data = json_decode(trim($tester->getDisplay()), TRUE);
    $this->assertIsArray($data);
    $this->assertSame('cli', $data['machine_name']);
  }

  public function testSchema(): void {
    $tester = $this->tester();
    $tester->execute(['--schema' => TRUE], ['interactive' => FALSE]);

    $schema = json_decode(trim($tester->getDisplay()), TRUE);
    $this->assertIsArray($schema);
    $this->assertArrayHasKey('prompts', $schema);
    $this->assertIsArray($schema['prompts']);
    $this->assertCount(38, $schema['prompts']);
  }

  public function testValidateRoundTrip(): void {
    // Collect a full set, then validate that exact set against the schema.
    $collect = $this->tester();
    $collect->execute(['--prompts' => '{"name":"Acme"}'], ['interactive' => FALSE]);
    $set = trim($collect->getDisplay());

    $validate = $this->tester();
    $exit = $validate->execute(['--validate' => $set], ['interactive' => FALSE]);

    $this->assertSame(Command::SUCCESS, $exit);
    $this->assertStringContainsString('valid', $validate->getDisplay());
  }

  public function testValidateRejectsBadOption(): void {
    $tester = $this->tester();

    $exit = $tester->execute(['--validate' => '{"name":"Acme","profile":"bogus"}'], ['interactive' => FALSE]);

    $this->assertSame(Command::FAILURE, $exit);
    $this->assertStringContainsString('profile', $tester->getDisplay());
  }

  public function testAgentHelp(): void {
    $tester = $this->tester();
    $tester->execute(['--agent-help' => TRUE], ['interactive' => FALSE]);

    // The agent help is a JSON Schema typing the answers object.
    $schema = json_decode($tester->getDisplay(), TRUE);
    $this->assertIsArray($schema);
    $this->assertSame('https://json-schema.org/draft/2020-12/schema', $schema['$schema']);
    $this->assertSame('string', $schema['properties']['name']['type']);
    $this->assertSame('VORTEX_NAME', $schema['properties']['name']['env']);
    $this->assertContains('name', $schema['required']);
    $this->assertContains('standard', $schema['properties']['profile']['enum']);
    $this->assertSame(['provided', 'environment', 'discovered', 'derived', 'default'], $schema['x-precedence']);
  }

  /**
   * Run the command and decode its JSON answer output.
   *
   * @param array<string,mixed> $args
   *   The command arguments.
   *
   * @return array<string,mixed>
   *   The decoded answers.
   */
  protected function collect(array $args): array {
    $tester = $this->tester();
    $tester->execute($args, ['interactive' => FALSE]);
    $data = json_decode(trim($tester->getDisplay()), TRUE);

    return is_array($data) ? $data : [];
  }

  /**
   * Build a tester for the Configure command.
   */
  protected function tester(): CommandTester {
    $application = new Application();
    $application->add(new ConfigureCommand());

    return new CommandTester($application->find('configure'));
  }

}
