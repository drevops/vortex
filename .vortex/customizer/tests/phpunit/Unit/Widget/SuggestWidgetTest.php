<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Widget;

use DrevOps\Customizer\Input\ArrayKeyStream;
use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;
use DrevOps\Customizer\Widget\AbstractWidget;
use DrevOps\Customizer\Widget\SuggestWidget;
use DrevOps\Customizer\Widget\WidgetRunner;
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
    $this->assertStringContainsString('Europe/London', $widget->view());
    $this->assertStringNotContainsString('Australia/Sydney', $widget->view());

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Down), Key::named(KeyName::Enter)));

    $this->assertSame('Europe/London', $value);
  }

  public function testEmptyBufferListsAll(): void {
    $widget = new SuggestWidget(['x', 'y']);

    $widget->handle(Key::named(KeyName::Down));
    $this->assertSame('x', $widget->value());
    $this->assertStringContainsString('y', $widget->view());
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

}
