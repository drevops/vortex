<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Schema;

use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\ConfigLoader;
use DrevOps\Tui\Schema\SchemaValidator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the schema validator.
 */
#[CoversClass(SchemaValidator::class)]
#[Group('schema')]
final class SchemaValidatorTest extends TestCase {

  public function testValidPasses(): void {
    $errors = (new SchemaValidator($this->config()))->validate([
      'name' => 'Acme',
      'profile' => 'standard',
      'agree' => TRUE,
      'mods' => ['a', 'b'],
    ]);

    $this->assertSame([], $errors);
  }

  public function testMissingRequired(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['profile' => 'standard']);

    $this->assertContains('Missing required question "name".', $errors);
  }

  public function testRequiredEmptyString(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => '']);

    $this->assertContains('Question "name" is required.', $errors);
  }

  public function testWrongType(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'agree' => 'yes']);

    $this->assertContains('Question "agree" must be a boolean.', $errors);
  }

  public function testInvalidSelectOption(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'profile' => 'bogus']);

    $this->assertContains('Question "profile" must be one of: standard, minimal.', $errors);
  }

  public function testInvalidMultiselectOption(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'mods' => ['a', 'z']]);

    $this->assertContains('Question "mods" contains an invalid option "z".', $errors);
  }

  public function testMultiselectWrongType(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'mods' => 'notalist']);

    $this->assertContains('Question "mods" must be a list.', $errors);
  }

  public function testUnknownQuestion(): void {
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'bogus' => 'x']);

    $this->assertContains('Unknown question "bogus".', $errors);
  }

  public function testInactiveRequiredFieldSkipped(): void {
    // 'custom' is required but only appears when profile == custom.
    $errors = (new SchemaValidator($this->config()))->validate(['name' => 'Acme', 'profile' => 'standard']);

    $this->assertSame([], $errors);
  }

  /**
   * Build a config exercising every validation branch.
   */
  protected function config(): Config {
    return (new ConfigLoader())->fromArray(['panels' => [['id' => 'p', 'fields' => [
      ['id' => 'name', 'type' => 'text', 'required' => TRUE],
      ['id' => 'profile', 'type' => 'select', 'options' => [['value' => 'standard'], ['value' => 'minimal']]],
      ['id' => 'agree', 'type' => 'confirm'],
      ['id' => 'mods', 'type' => 'multiselect', 'options' => [['value' => 'a'], ['value' => 'b']]],
      ['id' => 'custom', 'type' => 'text', 'required' => TRUE, 'when' => ['field' => 'profile', 'eq' => 'custom']],
    ]]]]);
  }

}
