<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Input\ArrayKeyStream;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Theme\DarkTheme;
use DrevOps\Tui\Widget\MultiSearchWidget;
use DrevOps\Tui\Widget\WidgetRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the multi-search widget.
 */
#[CoversClass(MultiSearchWidget::class)]
#[Group('widget')]
final class MultiSearchWidgetTest extends TestCase {

  /**
   * The options used across the tests.
   *
   * @var array<string,string>
   */
  protected array $labels = ['clamav' => 'ClamAV', 'redis' => 'Redis', 'solr' => 'Solr'];

  public function testFilterToggleAndAccept(): void {
    $widget = new MultiSearchWidget($this->labels);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of('sol', Key::named(KeyName::Space), Key::named(KeyName::Enter)));

    $this->assertSame(['solr'], $value);
  }

  public function testSeededSelectionKept(): void {
    $widget = new MultiSearchWidget($this->labels, ['redis']);

    $value = WidgetRunner::run($widget, ArrayKeyStream::of(Key::named(KeyName::Enter)));

    $this->assertSame(['redis'], $value);
  }

  public function testViewShowsQueryLineAboveOptions(): void {
    $widget = new MultiSearchWidget($this->labels);

    $widget->handle(Key::char('r'));
    $view = $widget->view(new DarkTheme());

    $this->assertStringContainsString("r│\n", $view);
    $this->assertStringContainsString('Redis', $view);
    $this->assertStringNotContainsString('ClamAV', $view);
  }

}
