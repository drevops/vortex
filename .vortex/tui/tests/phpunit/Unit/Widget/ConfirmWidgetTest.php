<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\AbstractWidget;
use DrevOps\Tui\Widget\ConfirmWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the confirm widget.
 */
#[CoversClass(ConfirmWidget::class)]
#[CoversClass(AbstractWidget::class)]
#[Group('widget')]
final class ConfirmWidgetTest extends TestCase {

  public function testDefaultAndToggle(): void {
    $widget = new ConfirmWidget(FALSE);
    $this->assertFalse($widget->value());
    $this->assertStringContainsString('● No', $widget->view(new DarkTheme()));

    $widget->handle(Key::named(KeyName::Space));
    $this->assertTrue($widget->value());
    $this->assertStringContainsString('● Yes', $widget->view(new DarkTheme()));
  }

  public function testCharYesNo(): void {
    $widget = new ConfirmWidget(FALSE);

    $widget->handle(Key::char('y'));
    $this->assertTrue($widget->value());

    $widget->handle(Key::char('n'));
    $this->assertFalse($widget->value());

    $widget->handle(Key::char('z'));
    $this->assertFalse($widget->value());
  }

  public function testAccept(): void {
    $widget = new ConfirmWidget(TRUE);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Enter)));

    $this->assertTrue($value);
    $this->assertTrue($widget->isComplete());
  }

  public function testCancel(): void {
    $widget = new ConfirmWidget(FALSE);

    $widget->handle(Key::named(KeyName::Escape));

    $this->assertTrue($widget->isCancelled());
  }

}
