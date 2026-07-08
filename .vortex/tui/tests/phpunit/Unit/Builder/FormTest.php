<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Builder;

use DrevOps\Tui\Builder\FieldBuilder;
use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Condition\Condition;
use DrevOps\Tui\Config\ConfigException;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Fixup;
use DrevOps\Tui\Derive\Derive;
use DrevOps\Tui\Discovery\Dotenv;
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
    $fixup = new Fixup(set: 'a', to: 'b', when: new Condition('x', eq: 'y'));

    $config = Form::create('Vortex', 'the project')
      ->theme('dark')
      ->banner('LOGO')
      ->buttons(TRUE, 'Install', 'Quit')
      ->clearOnExit(FALSE)
      ->color(TRUE)
      ->unicode(FALSE)
      ->envPrefix('APP_')
      ->fixup($fixup)
      ->panel('general', 'General', function (PanelBuilder $p): void {
        $p->description('General settings.');
        $p->text('name', 'Site name')->description('The name.')->required()->weight(10)->default('Acme');
        $p->text('machine_name', 'Machine name')->derive(new Derive('{{ name }}'));
        $p->select('profile', 'Profile')->options(['standard' => 'Standard', 'minimal' => 'Minimal'])->default('standard');
        $p->multiselect('services', 'Services')->option('solr', 'Solr', 'Search')->option('redis', 'Redis');
        $p->confirm('docs', 'Keep docs?')->default(TRUE)->when(new Condition('profile', eq: 'standard'));
        $p->suggest('timezone', 'Timezone')->discover(new Dotenv('TZ'));
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
    $this->assertSame('APP_', $config->envPrefix);
    $this->assertSame([$fixup], $config->fixups);
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
    $this->assertSame('{{ name }}', $machine->derive?->template);

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
    $this->assertSame(['field' => 'profile', 'eq' => 'standard'], $docs->when?->toArray());

    $timezone = $config->field('timezone');
    $this->assertInstanceOf(Field::class, $timezone);
    $this->assertSame(FieldType::Suggest, $timezone->type);
    $this->assertInstanceOf(Dotenv::class, $timezone->discover);
    $this->assertSame('TZ', $timezone->discover->key);

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
        $panel->number('n');
        $panel->textarea('ta');
        $panel->password('pw');
        $panel->search('se')->option('a');
        $panel->multisearch('ms')->option('a');
        $panel->pause('pa');
      })
      ->build();

    // Type defaults when none is declared.
    $this->assertSame('', $config->field('t')?->default);
    $this->assertSame('', $config->field('s')?->default);
    $this->assertSame([], $config->field('m')?->default);
    $this->assertFalse($config->field('c')?->default);
    $this->assertSame('', $config->field('g')?->default);
    $this->assertSame(0, $config->field('n')?->default);
    $this->assertSame('', $config->field('ta')?->default);
    $this->assertSame('', $config->field('pw')?->default);
    $this->assertSame('', $config->field('se')?->default);
    $this->assertSame([], $config->field('ms')?->default);
    // A pause defaults to acknowledged so headless runs never block on it.
    $this->assertTrue($config->field('pa')?->default);

    // Label and option-label fall back to the id/value.
    $this->assertSame('t', $config->field('t')->label);
    $this->assertSame('a', $config->field('s')->option('a')?->label);

    // Config-level defaults.
    $this->assertSame('', $config->subject);
    $this->assertTrue($config->buttons);
    $this->assertSame('Submit', $config->submitLabel);
    $this->assertSame('', $config->theme);
    $this->assertNull($config->color);
    $this->assertSame('', $config->envPrefix);
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
