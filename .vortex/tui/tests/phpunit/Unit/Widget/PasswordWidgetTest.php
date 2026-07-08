<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\PasswordWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the password widget.
 */
#[CoversClass(PasswordWidget::class)]
#[Group('widget')]
final class PasswordWidgetTest extends TestCase {

  public function testAcceptsPlainValue(): void {
    $widget = new PasswordWidget();

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('s3cret', Key::named(KeyName::Enter)));

    $this->assertSame('s3cret', $value);
  }

  public function testViewMasksEveryCharacter(): void {
    $widget = new PasswordWidget('abc');

    $view = $widget->view(new DarkTheme());

    $this->assertStringNotContainsString('abc', $view);
    $this->assertStringNotContainsString('a', $view);
    $this->assertSame(3, substr_count($view, '•'));
    $this->assertStringContainsString('│', $view);
  }

  public function testValidationErrorShownUnderMask(): void {
    $widget = new PasswordWidget('', fn(mixed $value): ?string => is_string($value) && $value !== '' ? NULL : 'Required.');

    $widget->handle(Key::named(KeyName::Enter));

    $this->assertFalse($widget->isComplete());
    $this->assertStringContainsString('Required.', $widget->view(new DarkTheme()));
  }

}
