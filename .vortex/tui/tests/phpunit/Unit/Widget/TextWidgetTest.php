<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\AbstractWidget;
use DrevOps\Tui\Widget\TextWidget;
use DrevOps\Tui\Widget\WidgetRunner;
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
    $this->assertStringContainsString('Required.', $widget->view(new DarkTheme()));

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
    $this->assertStringContainsString('│', $widget->view(new DarkTheme()));
  }

  public function testCancel(): void {
    $widget = new TextWidget('x');

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Escape)));

    $this->assertTrue($widget->isCancelled());
    $this->assertNull($value);
  }

  public function testSpaceInsertsSpace(): void {
    $widget = new TextWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::char('a'), Key::named(KeyName::Space), Key::char('b'), Key::named(KeyName::Enter)));

    $this->assertSame('a b', $value);
  }

}
