<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Builder;

use DrevOps\Customizer\Builder\FieldBuilder;
use DrevOps\Customizer\Builder\Form;
use DrevOps\Customizer\Builder\PanelBuilder;
use DrevOps\Customizer\Config\ConfigException;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
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

  public function testBuildsExpectedConfig(): void {
    $config = Form::create('Vortex', 'the project')
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

    $this->assertSame('Vortex', $config->title);
    $this->assertSame('the project', $config->subject);
    $this->assertSame('dark', $config->theme);
    $this->assertSame('LOGO', $config->banner);
    $this->assertTrue($config->buttons);
    $this->assertSame('Install', $config->submitLabel);
    $this->assertSame('Quit', $config->cancelLabel);
    $this->assertFalse($config->clearOnExit);
    $this->assertTrue($config->color);
    $this->assertFalse($config->unicode);
    $this->assertSame([['id' => 'dotenv', 'weight' => -1000], ['id' => 'internal', 'weight' => 1000]], $config->processors);
    $this->assertSame([['when' => ['x' => 'y'], 'set' => ['a' => 'b']]], $config->fixups);
    $this->assertSame('General settings.', $config->panels[0]->description);

    $name = $config->field('name');
    $this->assertInstanceOf(Field::class, $name);
    $this->assertSame('Site name', $name->label);
    $this->assertSame('The name.', $name->description);
    $this->assertSame(FieldType::Text, $name->type);
    $this->assertSame('Acme', $name->default);
    $this->assertTrue($name->required);
    $this->assertSame(10, $name->weight);

    $machine = $config->field('machine_name');
    $this->assertInstanceOf(Field::class, $machine);
    $this->assertTrue($machine->machine);
    $this->assertSame(['template' => '{{ name }}'], $machine->derive);

    $profile = $config->field('profile');
    $this->assertInstanceOf(Field::class, $profile);
    $this->assertSame(FieldType::Select, $profile->type);
    $this->assertSame('standard', $profile->default);
    $this->assertSame('Standard', $profile->option('standard')?->label);

    $services = $config->field('services');
    $this->assertInstanceOf(Field::class, $services);
    $this->assertSame(FieldType::MultiSelect, $services->type);
    $this->assertSame('Search', $services->option('solr')?->description);

    $docs = $config->field('docs');
    $this->assertInstanceOf(Field::class, $docs);
    $this->assertSame(FieldType::Confirm, $docs->type);
    $this->assertTrue($docs->default);
    $this->assertSame(['profile' => 'standard'], $docs->when);

    $timezone = $config->field('timezone');
    $this->assertInstanceOf(Field::class, $timezone);
    $this->assertSame(FieldType::Suggest, $timezone->type);
    $this->assertSame(['type' => 'dotenv', 'name' => 'TZ'], $timezone->discover);

    $webroot = $config->field('webroot');
    $this->assertInstanceOf(Field::class, $webroot);
    $this->assertSame('web', $webroot->default);
    $this->assertSame('Advanced', $config->panels[0]->panels[0]->title);
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
