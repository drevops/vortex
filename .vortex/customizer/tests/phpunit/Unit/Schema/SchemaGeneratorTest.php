<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Schema;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Schema\SchemaGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the schema generator.
 */
#[CoversClass(SchemaGenerator::class)]
#[Group('schema')]
final class SchemaGeneratorTest extends TestCase {

  public function testGenerate(): void {
    $config = (new ConfigLoader())->fromArray([
      'panels' => [['id' => 'p', 'fields' => [
        [
          'id' => 'profile',
          'type' => 'select',
          'label' => 'Profile',
          'description' => 'The profile',
          'default' => 'standard',
          'required' => TRUE,
          'options' => [
            ['value' => 'standard', 'label' => 'Standard', 'description' => 'Std'],
            ['value' => 'minimal', 'label' => 'Minimal'],
          ],
        ],
        [
          'id' => 'theme',
          'type' => 'text',
          'derive' => ['template' => '{{profile}}'],
          'when' => ['field' => 'profile', 'eq' => 'standard'],
        ],
      ]]],
    ]);

    $expected = [
      'prompts' => [
        [
          'id' => 'profile',
          'type' => 'select',
          'label' => 'Profile',
          'description' => 'The profile',
          'options' => [
            ['value' => 'standard', 'label' => 'Standard', 'description' => 'Std'],
            ['value' => 'minimal', 'label' => 'Minimal', 'description' => ''],
          ],
          'default' => 'standard',
          'required' => TRUE,
          'when' => NULL,
          'derive' => NULL,
          'discover' => NULL,
          'depends_on' => [],
        ],
        [
          'id' => 'theme',
          'type' => 'text',
          'label' => 'theme',
          'description' => '',
          'options' => [],
          'default' => '',
          'required' => FALSE,
          'when' => ['field' => 'profile', 'eq' => 'standard'],
          'derive' => ['template' => '{{profile}}'],
          'discover' => NULL,
          'depends_on' => ['profile'],
        ],
      ],
    ];

    $this->assertSame($expected, (new SchemaGenerator($config))->generate());
  }

  public function testRoundTripsThroughJson(): void {
    $config = (new ConfigLoader())->fromArray([
      'panels' => [['id' => 'p', 'fields' => [['id' => 'x', 'type' => 'confirm', 'default' => TRUE]]]],
    ]);

    $schema = (new SchemaGenerator($config))->generate();
    $decoded = json_decode((string) json_encode($schema), TRUE);

    $this->assertSame($schema, $decoded);
  }

}
