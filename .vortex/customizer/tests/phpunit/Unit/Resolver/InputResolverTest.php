<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Resolver;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Resolver\InputResolver;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the non-interactive input resolver.
 */
#[CoversClass(InputResolver::class)]
#[Group('resolver')]
final class InputResolverTest extends TestCase {

  public function testEnvCoercion(): void {
    $inputs = (new InputResolver('VORTEX_'))->resolve($this->fields(), '', [
      'VORTEX_NAME' => 'Acme',
      'VORTEX_AGREE' => 'yes',
      'VORTEX_MODS' => 'a, b ,c',
    ]);

    $this->assertSame('Acme', $inputs['name']);
    $this->assertTrue($inputs['agree']);
    $this->assertSame(['a', 'b', 'c'], $inputs['mods']);
  }

  public function testConfirmFalsey(): void {
    $inputs = (new InputResolver('VORTEX_'))->resolve($this->fields(), '', ['VORTEX_AGREE' => 'no']);

    $this->assertFalse($inputs['agree']);
  }

  public function testEmptyMultiselect(): void {
    $inputs = (new InputResolver('VORTEX_'))->resolve($this->fields(), '', ['VORTEX_MODS' => '']);

    $this->assertSame([], $inputs['mods']);
  }

  public function testPromptsJsonWinsOverEnv(): void {
    $inputs = (new InputResolver('VORTEX_'))->resolve($this->fields(), '{"name": "FromPrompts", "agree": true}', [
      'VORTEX_NAME' => 'FromEnv',
      'VORTEX_AGREE' => 'no',
    ]);

    $this->assertSame('FromPrompts', $inputs['name']);
    $this->assertTrue($inputs['agree']);
  }

  public function testMissingEnvOmitsField(): void {
    $this->assertSame([], (new InputResolver('VORTEX_'))->resolve($this->fields(), '', []));
  }

  public function testMalformedPromptsIgnored(): void {
    $inputs = (new InputResolver('VORTEX_'))->resolve($this->fields(), 'not json', ['VORTEX_NAME' => 'Acme']);

    $this->assertSame(['name' => 'Acme'], $inputs);
  }

  public function testPromptsFromFile(): void {
    vfsStream::setup('p', NULL, ['prompts.json' => '{"name": "FromFile"}']);

    $inputs = (new InputResolver('VORTEX_'))->resolve($this->fields(), vfsStream::url('p/prompts.json'), []);

    $this->assertSame('FromFile', $inputs['name']);
  }

  public function testEnvName(): void {
    $this->assertSame('VORTEX_MACHINE_NAME', (new InputResolver('VORTEX_'))->envName('machine_name'));
  }

  /**
   * Build a text, confirm and multiselect field for resolution.
   *
   * @return \DrevOps\Tui\Config\Field[]
   *   The fields.
   */
  protected function fields(): array {
    return [
      new Field('name', 'Name', '', FieldType::Text, ''),
      new Field('agree', 'Agree', '', FieldType::Confirm, FALSE),
      new Field('mods', 'Mods', '', FieldType::MultiSelect, []),
    ];
  }

}
