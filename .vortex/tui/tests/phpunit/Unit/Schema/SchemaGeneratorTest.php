<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Schema;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Schema\SchemaGenerator;
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
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $profile = $p->select('profile', 'Profile')->description('The profile')->default('standard')->required();
        $profile->option('standard', 'Standard', 'Std')->option('minimal', 'Minimal');
        $p->text('theme')->derive(new Derive('{{profile}}'))->when(new Condition('profile', eq: 'standard'));
      })
      ->build();

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

  public function testDependsOnCollectsNestedFieldRefs(): void {
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->text('a');
        $p->text('b');
        $p->text('c')->when(Condition::all(new Condition('a', eq: 'x'), new Condition('b', eq: 'y')));
      })
      ->build();

    $json = (string) json_encode((new SchemaGenerator($config))->generate());

    $this->assertStringContainsString('"depends_on":["a","b"]', $json);
  }

  public function testRoundTripsThroughJson(): void {
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->confirm('x')->default(TRUE);
      })
      ->build();

    $schema = (new SchemaGenerator($config))->generate();
    $decoded = json_decode((string) json_encode($schema), TRUE);

    $this->assertSame($schema, $decoded);
  }

}
