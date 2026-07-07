<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\AbstractWidget;
use DrevOps\Tui\Widget\SelectWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the select widget.
 */
#[CoversClass(SelectWidget::class)]
#[CoversClass(AbstractWidget::class)]
#[Group('widget')]
final class SelectWidgetTest extends TestCase {

  public function testNavigatesAndSelects(): void {
    $widget = new SelectWidget(['a' => 'Apple', 'b' => 'Banana', 'c' => 'Cherry'], 'a');

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(
      Key::named(KeyName::Down),
      Key::named(KeyName::Down),
      Key::named(KeyName::Up),
      Key::named(KeyName::Enter),
    ));

    $this->assertSame('b', $value);
    $this->assertStringContainsString('●', $widget->view(new DarkTheme()));
  }

  public function testDefaultHighlight(): void {
    $widget = new SelectWidget(['a' => 'A', 'b' => 'B'], 'b');

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Enter)));

    $this->assertSame('b', $value);
  }

  public function testBoundsClamp(): void {
    $widget = new SelectWidget(['a' => 'A', 'b' => 'B']);

    $widget->handle(Key::named(KeyName::Up));
    $this->assertSame('a', $widget->value());

    $widget->handle(Key::named(KeyName::Down));
    $widget->handle(Key::named(KeyName::Down));
    $this->assertSame('b', $widget->value());
  }

  public function testCancel(): void {
    $widget = new SelectWidget(['a' => 'A', 'b' => 'B']);

    $widget->handle(Key::named(KeyName::Escape));

    $this->assertTrue($widget->isCancelled());
  }

}
