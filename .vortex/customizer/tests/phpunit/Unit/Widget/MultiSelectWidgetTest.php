<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\AbstractWidget;
use DrevOps\Tui\Widget\MultiSelectWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the multiselect widget.
 */
#[CoversClass(MultiSelectWidget::class)]
#[CoversClass(AbstractWidget::class)]
#[Group('widget')]
final class MultiSelectWidgetTest extends TestCase {

  public function testToggleAndAccept(): void {
    $widget = new MultiSelectWidget(['a' => 'Apple', 'b' => 'Banana', 'c' => 'Cherry']);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(
      Key::named(KeyName::Space),
      Key::named(KeyName::Down),
      Key::named(KeyName::Space),
      Key::named(KeyName::Enter),
    ));

    $this->assertSame(['a', 'b'], $value);
  }

  public function testDefaultSelected(): void {
    $widget = new MultiSelectWidget(['a' => 'A', 'b' => 'B'], ['b']);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Enter)));

    $this->assertSame(['b'], $value);
  }

  public function testFilterNarrowsThenToggles(): void {
    $widget = new MultiSelectWidget(['apple' => 'Apple', 'apricot' => 'Apricot', 'banana' => 'Banana']);

    $widget->handle(Key::char('b'));
    $widget->handle(Key::char('a'));
    $widget->handle(Key::char('n'));
    $this->assertStringContainsString('Banana', $widget->view(new DarkTheme()));
    $this->assertStringNotContainsString('Apple', $widget->view(new DarkTheme()));

    $widget->handle(Key::named(KeyName::Space));
    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Enter)));

    $this->assertSame(['banana'], $value);
  }

  public function testFilterBackspaceRestoresList(): void {
    $widget = new MultiSelectWidget(['apple' => 'Apple', 'banana' => 'Banana']);

    $widget->handle(Key::char('b'));
    $this->assertStringNotContainsString('Apple', $widget->view(new DarkTheme()));

    $widget->handle(Key::named(KeyName::Backspace));
    $this->assertStringContainsString('Apple', $widget->view(new DarkTheme()));
  }

  public function testSelectAllAndNone(): void {
    $widget = new MultiSelectWidget(['a' => 'A', 'b' => 'B', 'c' => 'C']);

    $widget->handle(Key::named(KeyName::Right));
    $this->assertSame(['a', 'b', 'c'], $widget->value());

    $widget->handle(Key::named(KeyName::Left));
    $this->assertSame([], $widget->value());
  }

  public function testCancel(): void {
    $widget = new MultiSelectWidget(['a' => 'A', 'b' => 'B']);

    WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Escape)));

    $this->assertTrue($widget->isCancelled());
  }

  public function testUpMovesCursorBack(): void {
    $widget = new MultiSelectWidget(['a' => 'A', 'b' => 'B']);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(
      Key::named(KeyName::Down),
      Key::named(KeyName::Up),
      Key::named(KeyName::Space),
      Key::named(KeyName::Enter),
    ));

    $this->assertSame(['a'], $value);
  }

  public function testToggleOffDeselects(): void {
    $widget = new MultiSelectWidget(['a' => 'A', 'b' => 'B'], ['b']);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(
      Key::named(KeyName::Down),
      Key::named(KeyName::Space),
      Key::named(KeyName::Enter),
    ));

    $this->assertSame([], $value);
  }

  public function testToggleWithNoMatchesIsNoop(): void {
    $widget = new MultiSelectWidget(['a' => 'Apple']);

    $widget->handle(Key::char('z'));
    $value = WidgetRunner::run($widget, ArrayKeyStream::of(
      Key::named(KeyName::Space),
      Key::named(KeyName::Enter),
    ));

    $this->assertSame([], $value);
  }

}
