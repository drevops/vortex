<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\AbstractWidget;
use DrevOps\Tui\Widget\SuggestWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the suggest (autocomplete) widget.
 */
#[CoversClass(SuggestWidget::class)]
#[CoversClass(AbstractWidget::class)]
#[Group('widget')]
final class SuggestWidgetTest extends TestCase {

  public function testTypeAcceptsBuffer(): void {
    $widget = new SuggestWidget(['UTC', 'Europe/London', 'Australia/Sydney']);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('UTC', Key::named(KeyName::Enter)));

    $this->assertSame('UTC', $value);
  }

  public function testNarrowsAndSelectsSuggestion(): void {
    $widget = new SuggestWidget(['UTC', 'Europe/London', 'Australia/Sydney']);

    $widget->handle(Key::char('l'));
    $widget->handle(Key::char('o'));
    $widget->handle(Key::char('n'));
    $this->assertStringContainsString('Europe/London', $widget->view(new DarkTheme()));
    $this->assertStringNotContainsString('Australia/Sydney', $widget->view(new DarkTheme()));

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Down), Key::named(KeyName::Enter)));

    $this->assertSame('Europe/London', $value);
  }

  public function testEmptyBufferListsAll(): void {
    $widget = new SuggestWidget(['x', 'y']);

    $widget->handle(Key::named(KeyName::Down));
    $this->assertSame('x', $widget->value());
    $this->assertStringContainsString('y', $widget->view(new DarkTheme()));
  }

  public function testBackspaceAndUpResetHighlight(): void {
    $widget = new SuggestWidget(['abc', 'abd']);

    $widget->handle(Key::char('a'));
    $widget->handle(Key::named(KeyName::Down));
    $this->assertSame('abc', $widget->value());

    $widget->handle(Key::named(KeyName::Up));
    $this->assertSame('a', $widget->value());

    $widget->handle(Key::char('b'));
    $widget->handle(Key::named(KeyName::Backspace));
    $this->assertSame('a', $widget->value());
  }

  public function testCancel(): void {
    $widget = new SuggestWidget(['x', 'y']);

    $widget->handle(Key::named(KeyName::Escape));

    $this->assertTrue($widget->isCancelled());
  }

  public function testSpaceAppendsToBuffer(): void {
    $widget = new SuggestWidget(['x', 'y']);

    $widget->handle(Key::char('a'));
    $widget->handle(Key::named(KeyName::Space));

    $this->assertSame('a ', $widget->value());
  }

}
