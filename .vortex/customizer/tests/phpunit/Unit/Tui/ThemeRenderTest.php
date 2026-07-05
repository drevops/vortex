<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Tui;

use DrevOps\Customizer\Answers\Answers;
use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
use DrevOps\Customizer\Config\Panel;
use DrevOps\Customizer\Tui\Ansi;
use DrevOps\Customizer\Tui\DarkTheme;
use DrevOps\Customizer\Tui\Navigator;
use DrevOps\Customizer\Tui\Theme;
use DrevOps\Customizer\Tui\Viewport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the theme's rendering via headless frame probes.
 */
#[CoversClass(Theme::class)]
#[Group('tui')]
final class ThemeRenderTest extends TestCase {

  public function testFieldLineSelectedRightAlignsBadge(): void {
    $line = $this->theme()->fieldLine(new Field('name', 'Name', '', FieldType::Text, ''), new Answers(['name' => 'Acme'], ['name' => 'edited']), TRUE);

    $this->assertStringContainsString('❯ Name  Acme', Ansi::strip($line));
    $this->assertStringContainsString('edited', Ansi::strip($line));
    $this->assertSame(40, Ansi::width($line));
  }

  public function testFieldLineDefaultHasNoBadge(): void {
    $line = $this->theme()->fieldLine(new Field('name', 'Name', '', FieldType::Text, ''), new Answers(['name' => 'Acme'], ['name' => 'default']), FALSE);

    $this->assertStringNotContainsString('default', $line);
    $this->assertStringContainsString('Name  Acme', Ansi::strip($line));
  }

  public function testFieldLineRendersValues(): void {
    $theme = $this->theme();

    $bool = Ansi::strip($theme->fieldLine(new Field('b', 'B', '', FieldType::Confirm, FALSE), new Answers(['b' => TRUE], ['b' => 'default']), FALSE));
    $this->assertStringContainsString('B  yes', $bool);

    $list = Ansi::strip($theme->fieldLine(new Field('m', 'M', '', FieldType::MultiSelect, []), new Answers(['m' => ['a', 'b']], ['m' => 'default']), FALSE));
    $this->assertStringContainsString('M  a, b', $list);
  }

  public function testPanelLineShowsDrillIndicator(): void {
    $line = Ansi::strip($this->theme()->panelLine(new Panel('adv', 'Advanced', ''), TRUE));

    $this->assertStringContainsString('❯ Advanced', $line);
    $this->assertStringContainsString('›', $line);
  }

  public function testBodyReportsCursorLine(): void {
    $panel = new Panel('p', 'P', '', [
      new Field('a', 'A', 'desc a', FieldType::Text, ''),
      new Field('b', 'B', '', FieldType::Text, ''),
    ]);

    [$lines, $cursor_line] = $this->theme()->body($panel, new Answers(), 1);

    $this->assertSame(2, $cursor_line);
    $this->assertStringContainsString('❯ B', Ansi::strip($lines[2]));
  }

  public function testBodyIncludesSubPanels(): void {
    $panel = new Panel('p', 'P', '', [new Field('a', 'A', '', FieldType::Text, '')], [
      new Panel('sub', 'Sub', 'sub desc'),
    ]);

    [$lines, $cursor_line] = $this->theme()->body($panel, new Answers(), 1);

    // The cursor is on the sub-panel (index 1, after the single field).
    $this->assertSame(1, $cursor_line);
    $this->assertStringContainsString('❯ Sub', Ansi::strip($lines[1]));
    $this->assertStringContainsString('sub desc', Ansi::strip($lines[2]));
  }

  public function testFrameShowsIndicatorsAndWindow(): void {
    $body = array_map(static fn(int $i): string => 'line' . $i, range(0, 9));

    $frame = $this->theme()->frame(['HEAD'], $body, ['FOOT'], new Viewport(3, TRUE, TRUE), 4);

    $this->assertStringContainsString('▲', $frame);
    $this->assertStringContainsString('▼', $frame);
    $this->assertStringContainsString('HEAD', $frame);
    $this->assertStringContainsString('FOOT', $frame);
    $this->assertStringContainsString('line3', $frame);
    $this->assertStringNotContainsString('line0', $frame);
  }

  public function testBreadcrumbLine(): void {
    $navigator = new Navigator(new Panel('hub', 'Hub', '', [], [new Panel('d', 'Drupal', '')]));

    $this->assertSame('Hub', Ansi::strip($this->theme()->breadcrumbLine($navigator)));
  }

  public function testBanner(): void {
    $banner = Ansi::strip($this->theme()->banner("LOGO\nline", '1.2.3'));

    $this->assertStringContainsString('LOGO', $banner);
    $this->assertStringContainsString('Version: 1.2.3', $banner);

    $this->assertStringNotContainsString('Version', Ansi::strip($this->theme()->banner('LOGO', '')));
  }

  public function testItemCount(): void {
    $panel = new Panel('p', 'P', '', [new Field('a', 'A', '', FieldType::Text, '')], [new Panel('s', 'S', '')]);

    $this->assertSame(2, $this->theme()->itemCount($panel));
  }

  public function testStatusLineIsThemed(): void {
    $line = (new DarkTheme())->statusLine();

    // Themed with the footer role (dim) and composed from arrow glyphs.
    $this->assertStringContainsString("\033[2m", $line);
    $this->assertStringContainsString('↑/↓ move', Ansi::strip($line));
  }

  public function testButtonLine(): void {
    $theme = $this->theme();

    $selected = Ansi::strip($theme->buttonLine('Submit', TRUE));
    $this->assertStringContainsString('❯ [ Submit ]', $selected);

    $unselected = Ansi::strip($theme->buttonLine('Cancel', FALSE));
    $this->assertStringContainsString('[ Cancel ]', $unselected);
    $this->assertStringNotContainsString('❯', $unselected);
  }

  /**
   * A colourless theme of fixed width for stable assertions.
   */
  protected function theme(): DarkTheme {
    return new DarkTheme(FALSE, 40);
  }

}
