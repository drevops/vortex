<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Config;

use DrevOps\Tui\Builder\Form;
use DrevOps\Tui\Builder\PanelBuilder;
use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Option;
use DrevOps\Tui\Config\Panel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the immutable configuration model built by the fluent form builder.
 */
#[CoversClass(Config::class)]
#[CoversClass(Panel::class)]
#[CoversClass(Field::class)]
#[CoversClass(Option::class)]
#[Group('config')]
final class ConfigModelTest extends TestCase {

  public function testBuildsNestedConfig(): void {
    $config = Form::create('Demo', 'Acme')
      ->panel('general', 'General', function (PanelBuilder $p): void {
        $p->text('name')->default('Acme')->required();
        $p->text('email');
      })
      ->panel('drupal', 'Drupal', function (PanelBuilder $p): void {
        $p->select('profile')->option('standard', 'Standard');
        $p->panel('advanced', 'Advanced', function (PanelBuilder $sp): void {
          $sp->confirm('theme_debug');
        });
      })
      ->build();

    $this->assertSame('Demo', $config->title);
    $this->assertSame('Acme', $config->subject);
    $this->assertCount(2, $config->panels);

    $general = $config->panels[0];
    $this->assertSame('general', $general->id);
    $this->assertCount(2, $general->fields);

    $name = $general->fields[0];
    $this->assertSame(FieldType::Text, $name->type);
    $this->assertSame('Acme', $name->default);
    $this->assertTrue($name->required);

    $drupal = $config->panels[1];
    $profile = $drupal->fields[0];
    $this->assertSame(FieldType::Select, $profile->type);
    $standard = $profile->option('standard');
    $this->assertInstanceOf(Option::class, $standard);
    $this->assertSame('Standard', $standard->label);
    $this->assertNotInstanceOf(Option::class, $profile->option('missing'));

    $this->assertCount(1, $drupal->panels);
    $this->assertSame('advanced', $drupal->panels[0]->id);

    // field() resolves nested fields across sub-panels.
    $this->assertSame('theme_debug', $config->field('theme_debug')?->id);
    $this->assertNotInstanceOf(Field::class, $config->field('nope'));
    $this->assertCount(4, $config->fields());
  }

  public function testTypeDefaults(): void {
    $config = Form::create('T')
      ->panel('p', 'p', function (PanelBuilder $p): void {
        $p->multiselect('ms');
        $p->confirm('cb');
        $p->text('tx');
      })
      ->build();

    $this->assertSame([], $config->field('ms')?->default);
    $this->assertFalse($config->field('cb')?->default);
    $this->assertSame('', $config->field('tx')?->default);
  }

  public function testRootDefaults(): void {
    $config = Form::create('T')->build();

    $this->assertTrue($config->buttons);
    $this->assertSame('Submit', $config->submitLabel);
    $this->assertSame('Cancel', $config->cancelLabel);
    $this->assertTrue($config->clearOnExit);
    $this->assertSame('', $config->banner);
    $this->assertNull($config->color);
    $this->assertNull($config->unicode);
    $this->assertSame([], $config->processors);
    $this->assertSame([], $config->fixups);
  }

}
