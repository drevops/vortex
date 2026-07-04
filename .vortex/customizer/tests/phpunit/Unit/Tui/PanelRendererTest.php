<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Tui;

use DrevOps\Customizer\Answers\Answers;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
use DrevOps\Customizer\Config\Panel;
use DrevOps\Customizer\Tui\Ansi;
use DrevOps\Customizer\Tui\Navigator;
use DrevOps\Customizer\Tui\PanelRenderer;
use DrevOps\Customizer\Tui\Theme;
use DrevOps\Customizer\Tui\Viewport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the panel renderer via headless frame probes.
 */
#[CoversClass(PanelRenderer::class)]
#[Group('tui')]
final class PanelRendererTest extends TestCase {

  public function testFieldLineSelectedRightAlignsBadge(): void {
    $line = $this->renderer()->fieldLine(new Field('name', 'Name', '', FieldType::Text, ''), new Answers(['name' => 'Acme'], ['name' => 'edited']), TRUE);

    $this->assertStringContainsString('❯ Name  Acme', Ansi::strip($line));
    $this->assertStringContainsString('edited', Ansi::strip($line));
    $this->assertSame(40, Ansi::width($line));
  }

  public function testFieldLineDefaultHasNoBadge(): void {
    $line = $this->renderer()->fieldLine(new Field('name', 'Name', '', FieldType::Text, ''), new Answers(['name' => 'Acme'], ['name' => 'default']), FALSE);

    $this->assertStringNotContainsString('default', $line);
    $this->assertStringContainsString('Name  Acme', Ansi::strip($line));
  }

  public function testPanelLineShowsDrillIndicator(): void {
    $line = Ansi::strip($this->renderer()->panelLine(new Panel('adv', 'Advanced', ''), TRUE));

    $this->assertStringContainsString('❯ Advanced', $line);
    $this->assertStringContainsString('›', $line);
  }

  public function testBodyReportsCursorLine(): void {
    $panel = new Panel('p', 'P', '', [
      new Field('a', 'A', 'desc a', FieldType::Text, ''),
      new Field('b', 'B', '', FieldType::Text, ''),
    ]);

    [$lines, $cursor_line] = $this->renderer()->body($panel, new Answers(), 1);

    $this->assertSame(2, $cursor_line);
    $this->assertStringContainsString('❯ B', Ansi::strip($lines[2]));
  }

  public function testFrameShowsIndicatorsAndWindow(): void {
    $body = array_map(static fn(int $i): string => 'line' . $i, range(0, 9));

    $frame = $this->renderer()->frame(['HEAD'], $body, ['FOOT'], new Viewport(3, TRUE, TRUE), 4);

    $this->assertStringContainsString('▲', $frame);
    $this->assertStringContainsString('▼', $frame);
    $this->assertStringContainsString('HEAD', $frame);
    $this->assertStringContainsString('FOOT', $frame);
    $this->assertStringContainsString('line3', $frame);
    $this->assertStringNotContainsString('line0', $frame);
  }

  public function testBreadcrumbLine(): void {
    $navigator = new Navigator(new Panel('hub', 'Hub', '', [], [new Panel('d', 'Drupal', '')]));

    $this->assertSame('Hub', Ansi::strip($this->renderer()->breadcrumbLine($navigator)));
  }

  /**
   * A colourless renderer of fixed width for stable assertions.
   */
  protected function renderer(): PanelRenderer {
    return new PanelRenderer(new Theme('default', [], FALSE), 40);
  }

}
