<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Widget;

use DrevOps\Customizer\Input\ArrayKeyStream;
use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;
use DrevOps\Customizer\Widget\AbstractWidget;
use DrevOps\Customizer\Widget\TextWidget;
use DrevOps\Customizer\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the text widget.
 */
#[CoversClass(TextWidget::class)]
#[CoversClass(AbstractWidget::class)]
#[CoversClass(WidgetRunner::class)]
#[Group('widget')]
final class TextWidgetTest extends TestCase {

  public function testTypesAndAccepts(): void {
    $widget = new TextWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('Acme', Key::named(KeyName::Enter)));

    $this->assertSame('Acme', $value);
    $this->assertTrue($widget->isComplete());
  }

  public function testTransformApplied(): void {
    $widget = new TextWidget('', NULL, fn(mixed $value): string => is_string($value) ? strtoupper($value) : '');

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('acme', Key::named(KeyName::Enter)));

    $this->assertSame('ACME', $value);
  }

  public function testValidationBlocksThenAccepts(): void {
    $validate = fn(mixed $value): ?string => is_string($value) && $value !== '' ? NULL : 'Required.';
    $widget = new TextWidget('', $validate);

    $widget->handle(Key::named(KeyName::Enter));
    $this->assertFalse($widget->isComplete());
    $this->assertSame('Required.', $widget->error());
    $this->assertStringContainsString('Required.', $widget->view());

    $widget->handle(Key::char('a'));
    $widget->handle(Key::char('b'));
    $widget->handle(Key::named(KeyName::Enter));

    $this->assertTrue($widget->isComplete());
    $this->assertNull($widget->error());
    $this->assertSame('ab', $widget->value());
  }

  public function testCursorEditingAndBackspace(): void {
    $widget = new TextWidget('ac');

    $widget->handle(Key::named(KeyName::Left));
    $widget->handle(Key::char('b'));
    $this->assertSame('abc', $widget->value());

    $widget->handle(Key::named(KeyName::Backspace));
    $this->assertSame('ac', $widget->value());

    $widget->handle(Key::named(KeyName::Right));
    $this->assertStringContainsString('|', $widget->view());
  }

  public function testCancel(): void {
    $widget = new TextWidget('x');

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Escape)));

    $this->assertTrue($widget->isCancelled());
    $this->assertNull($value);
  }

}
