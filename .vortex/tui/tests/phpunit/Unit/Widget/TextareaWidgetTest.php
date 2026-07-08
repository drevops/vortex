<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\TextareaWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the textarea widget.
 */
#[CoversClass(TextareaWidget::class)]
#[Group('widget')]
final class TextareaWidgetTest extends TestCase {

  public function testEnterInsertsNewlineAndTabAccepts(): void {
    $widget = new TextareaWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('one', Key::named(KeyName::Enter), 'two', Key::named(KeyName::Tab)));

    $this->assertSame("one\ntwo", $value);
    $this->assertTrue($widget->isComplete());
  }

  public function testUpAndDownMoveAcrossLines(): void {
    $widget = new TextareaWidget("ab\ncd");

    // The cursor starts at the end of "cd"; Up keeps the column on "ab".
    $widget->handle(Key::named(KeyName::Up));
    $widget->handle(Key::char('X'));

    $this->assertSame("abX\ncd", $widget->value());

    $widget->handle(Key::named(KeyName::Down));
    $widget->handle(Key::char('Y'));

    $this->assertSame("abX\ncdY", $widget->value());
  }

  public function testUpClampsAtFirstLineAndDownAtLast(): void {
    $widget = new TextareaWidget('solo');

    $widget->handle(Key::named(KeyName::Up));
    $widget->handle(Key::named(KeyName::Down));
    $widget->handle(Key::named(KeyName::Tab));

    $this->assertSame('solo', $widget->value());
  }

  public function testUpFromLongerLineClampsColumn(): void {
    $widget = new TextareaWidget("a\nlonger");

    $widget->handle(Key::named(KeyName::Up));
    $widget->handle(Key::char('Z'));

    $this->assertSame("aZ\nlonger", $widget->value());
  }

  public function testViewShowsHintAndError(): void {
    $widget = new TextareaWidget('x', fn(mixed $value): ?string => 'Nope.');

    $view = $widget->view(new DarkTheme());
    $this->assertStringContainsString('tab accept', $view);

    $widget->handle(Key::named(KeyName::Tab));
    $this->assertStringContainsString('Nope.', $widget->view(new DarkTheme()));
  }

  public function testCancel(): void {
    $widget = new TextareaWidget('x');

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Escape)));

    $this->assertTrue($widget->isCancelled());
    $this->assertNull($value);
  }

}
