<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\SearchWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the search widget.
 */
#[CoversClass(SearchWidget::class)]
#[Group('widget')]
final class SearchWidgetTest extends TestCase {

  /**
   * The options used across the tests.
   *
   * @var array<string,string>
   */
  protected array $labels = ['gha' => 'GitHub Actions', 'circleci' => 'CircleCI', 'none' => 'None'];

  public function testFilterNarrowsAndEnterAcceptsValue(): void {
    $widget = new SearchWidget($this->labels);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('circle', Key::named(KeyName::Enter)));

    $this->assertSame('circleci', $value);
  }

  public function testDefaultSeedsHighlight(): void {
    $widget = new SearchWidget($this->labels, 'none');

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Enter)));

    $this->assertSame('none', $value);
  }

  public function testArrowsMoveHighlight(): void {
    $widget = new SearchWidget($this->labels);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Down), Key::named(KeyName::Enter)));

    $this->assertSame('circleci', $value);
  }

  public function testEnterIgnoredWhenNothingMatches(): void {
    $widget = new SearchWidget($this->labels);

    $widget->handle(Key::char('z'));
    $widget->handle(Key::char('z'));
    $widget->handle(Key::named(KeyName::Enter));

    $this->assertFalse($widget->isComplete());

    $widget->handle(Key::named(KeyName::Backspace));
    $widget->handle(Key::named(KeyName::Backspace));
    $widget->handle(Key::named(KeyName::Enter));

    $this->assertTrue($widget->isComplete());
    $this->assertSame('gha', $widget->value());
  }

  public function testSpaceIsPartOfTheQuery(): void {
    $widget = new SearchWidget($this->labels);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('hub', Key::named(KeyName::Space), Key::named(KeyName::Backspace), Key::named(KeyName::Enter)));

    $this->assertSame('gha', $value);
  }

  public function testViewShowsQueryAndVisibleOptions(): void {
    $widget = new SearchWidget($this->labels);

    $widget->handle(Key::char('c'));
    $view = $widget->view(new DarkTheme());

    $this->assertStringContainsString('c│', $view);
    $this->assertStringContainsString('CircleCI', $view);
    $this->assertStringNotContainsString('None', $view);
  }

  public function testCancel(): void {
    $widget = new SearchWidget($this->labels);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Escape)));

    $this->assertTrue($widget->isCancelled());
    $this->assertNull($value);
  }

}
