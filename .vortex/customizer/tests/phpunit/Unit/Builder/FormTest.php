<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Builder;

use DrevOps\Customizer\Builder\FieldBuilder;
use DrevOps\Customizer\Builder\Form;
use DrevOps\Customizer\Builder\PanelBuilder;
use DrevOps\Customizer\Config\ConfigException;
use DrevOps\Customizer\Config\ConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the fluent form builder.
 */
#[CoversClass(Form::class)]
#[CoversClass(PanelBuilder::class)]
#[CoversClass(FieldBuilder::class)]
#[Group('config')]
final class FormTest extends TestCase {

  public function testBuilderMatchesLoader(): void {
    $built = Form::create('Vortex', 'the project')
      ->theme('dark')
      ->banner('LOGO')
      ->buttons(TRUE, 'Install', 'Quit')
      ->clearOnExit(FALSE)
      ->color(TRUE)
      ->unicode(FALSE)
      ->processor('dotenv', -1000)
      ->processor('internal', 1000)
      ->fixup(['when' => ['x' => 'y'], 'set' => ['a' => 'b']])
      ->panel('general', 'General', function (PanelBuilder $p): void {
        $p->description('General settings.');
        $p->text('name', 'Site name')->description('The name.')->required()->weight(10)->default('Acme');
        $p->text('machine_name', 'Machine name')->machine()->derive(['template' => '{{ name }}']);
        $p->select('profile', 'Profile')->options(['standard' => 'Standard', 'minimal' => 'Minimal'])->default('standard');
        $p->multiselect('services', 'Services')->option('solr', 'Solr', 'Search')->option('redis', 'Redis');
        $p->confirm('docs', 'Keep docs?')->default(TRUE)->when(['profile' => 'standard']);
        $p->suggest('timezone', 'Timezone')->discover(['type' => 'dotenv', 'name' => 'TZ']);
        $p->panel('advanced', 'Advanced', function (PanelBuilder $sp): void {
          $sp->text('webroot', 'Web root')->default('web');
        });
      })
      ->build();

    $loaded = (new ConfigLoader())->fromArray([
      'title' => 'Vortex',
      'subject' => 'the project',
      'theme' => 'dark',
      'banner' => 'LOGO',
      'buttons' => ['submit' => 'Install', 'cancel' => 'Quit'],
      'clear_on_exit' => FALSE,
      'color' => TRUE,
      'unicode' => FALSE,
      'processors' => [['id' => 'dotenv', 'weight' => -1000], ['id' => 'internal', 'weight' => 1000]],
      'fixups' => [['when' => ['x' => 'y'], 'set' => ['a' => 'b']]],
      'panels' => [
        [
          'id' => 'general',
          'title' => 'General',
          'description' => 'General settings.',
          'fields' => [
            ['id' => 'name', 'label' => 'Site name', 'description' => 'The name.', 'type' => 'text', 'required' => TRUE, 'weight' => 10, 'default' => 'Acme'],
            ['id' => 'machine_name', 'label' => 'Machine name', 'type' => 'text', 'machine' => TRUE, 'derive' => ['template' => '{{ name }}']],
            ['id' => 'profile', 'label' => 'Profile', 'type' => 'select', 'default' => 'standard', 'options' => [['value' => 'standard', 'label' => 'Standard'], ['value' => 'minimal', 'label' => 'Minimal']]],
            ['id' => 'services', 'label' => 'Services', 'type' => 'multiselect', 'options' => [['value' => 'solr', 'label' => 'Solr', 'description' => 'Search'], ['value' => 'redis', 'label' => 'Redis']]],
            ['id' => 'docs', 'label' => 'Keep docs?', 'type' => 'confirm', 'default' => TRUE, 'when' => ['profile' => 'standard']],
            ['id' => 'timezone', 'label' => 'Timezone', 'type' => 'suggest', 'discover' => ['type' => 'dotenv', 'name' => 'TZ']],
          ],
          'panels' => [
            ['id' => 'advanced', 'title' => 'Advanced', 'fields' => [['id' => 'webroot', 'label' => 'Web root', 'type' => 'text', 'default' => 'web']]],
          ],
        ],
      ],
    ]);

    $this->assertEquals($loaded, $built);
  }

  public function testDefaultsAndFallbacks(): void {
    $config = Form::create('T')
      ->panel('p', 'P', function (PanelBuilder $panel): void {
        $panel->text('t');
        $panel->select('s')->option('a');
        $panel->multiselect('m');
        $panel->confirm('c');
        $panel->suggest('g');
      })
      ->build();

    // Type defaults when none is declared.
    $this->assertSame('', $config->field('t')?->default);
    $this->assertSame('', $config->field('s')?->default);
    $this->assertSame([], $config->field('m')?->default);
    $this->assertFalse($config->field('c')?->default);
    $this->assertSame('', $config->field('g')?->default);

    // Label and option-label fall back to the id/value.
    $this->assertSame('t', $config->field('t')->label);
    $this->assertSame('a', $config->field('s')->option('a')?->label);

    // Config-level defaults.
    $this->assertSame('', $config->subject);
    $this->assertTrue($config->buttons);
    $this->assertSame('Submit', $config->submitLabel);
    $this->assertSame('', $config->theme);
    $this->assertNull($config->color);
    $this->assertSame('', $config->panels[0]->description);
  }

  public function testDuplicateFieldIdThrows(): void {
    $this->expectException(ConfigException::class);
    $this->expectExceptionMessage('Duplicate field id "x".');

    Form::create('T')
      ->panel('a', 'A', fn(PanelBuilder $p): FieldBuilder => $p->text('x'))
      ->panel('b', 'B', fn(PanelBuilder $p): FieldBuilder => $p->text('x'))
      ->build();
  }

}
