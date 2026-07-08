<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\PauseWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the pause widget.
 */
#[CoversClass(PauseWidget::class)]
#[Group('widget')]
final class PauseWidgetTest extends TestCase {

  public function testEnterAcknowledges(): void {
    $widget = new PauseWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Enter)));

    $this->assertTrue($value);
    $this->assertTrue($widget->isComplete());
  }

  public function testSpaceAcknowledges(): void {
    $widget = new PauseWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Space)));

    $this->assertTrue($value);
  }

  public function testOtherKeysIgnored(): void {
    $widget = new PauseWidget();

    $widget->handle(Key::char('x'));
    $widget->handle(Key::named(KeyName::Down));

    $this->assertFalse($widget->isComplete());
    $this->assertFalse($widget->value());
  }

  public function testCancelAndView(): void {
    $widget = new PauseWidget();

    $this->assertStringContainsString('Press Enter to continue', $widget->view(new DarkTheme()));

    $widget->handle(Key::named(KeyName::Escape));
    $this->assertTrue($widget->isCancelled());
  }

}
