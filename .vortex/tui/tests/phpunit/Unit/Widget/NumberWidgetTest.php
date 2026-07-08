<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\NumberWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the number widget.
 */
#[CoversClass(NumberWidget::class)]
#[Group('widget')]
final class NumberWidgetTest extends TestCase {

  public function testTypesDigitsAndAcceptsInt(): void {
    $widget = new NumberWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('8080', Key::named(KeyName::Enter)));

    $this->assertSame(8080, $value);
    $this->assertTrue($widget->isComplete());
  }

  public function testRejectsNonDigits(): void {
    $widget = new NumberWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('4a2 x!', Key::named(KeyName::Enter)));

    $this->assertSame(42, $value);
  }

  public function testLeadingMinusOnly(): void {
    $widget = new NumberWidget();

    $widget->handle(Key::char('-'));
    $widget->handle(Key::char('7'));
    // A second minus, no longer at the start, is ignored.
    $widget->handle(Key::char('-'));
    $widget->handle(Key::named(KeyName::Enter));

    $this->assertSame(-7, $widget->value());
  }

  public function testMinusRejectedMidBuffer(): void {
    $widget = new NumberWidget('12');

    $widget->handle(Key::named(KeyName::Left));
    $widget->handle(Key::named(KeyName::Left));
    // The cursor is at the start, but a minus cannot join an existing one.
    $widget->handle(Key::char('-'));
    $widget->handle(Key::named(KeyName::Enter));

    $this->assertSame(-12, $widget->value());
  }

  public function testEmptyBufferAcceptsZero(): void {
    $widget = new NumberWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Enter)));

    $this->assertSame(0, $value);
  }

  public function testSeededFromCurrentAndRendersCaret(): void {
    $widget = new NumberWidget('42');

    $this->assertStringContainsString('42', $widget->view(new DarkTheme()));
    $this->assertStringContainsString('│', $widget->view(new DarkTheme()));
  }

}
