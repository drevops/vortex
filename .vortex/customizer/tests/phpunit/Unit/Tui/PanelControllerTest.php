<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Tui;

use DrevOps\Customizer\Config\ConfigLoader;
use DrevOps\Customizer\Input\Key;
use DrevOps\Customizer\Input\KeyName;
use DrevOps\Customizer\Tui\Ansi;
use DrevOps\Customizer\Tui\PanelController;
use DrevOps\Customizer\Tui\DarkTheme;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the interactive panel controller.
 */
#[CoversClass(PanelController::class)]
#[Group('tui')]
final class PanelControllerTest extends TestCase {

  public function testDrillIntoPanelAndBack(): void {
    $controller = $this->controller();

    $controller->handle(Key::named(KeyName::Enter));
    $this->assertSame('General', $controller->currentPanel()->title);

    $controller->handle(Key::named(KeyName::Escape));
    $this->assertSame('Demo', $controller->currentPanel()->title);
  }

  public function testNavigateCursorClamps(): void {
    $controller = $this->controller();
    $this->assertSame(0, $controller->cursor());

    $controller->handle(Key::named(KeyName::Down));
    $this->assertSame(1, $controller->cursor());

    // The root holds 2 panels plus the Submit and Cancel buttons (4 items).
    $controller->handle(Key::named(KeyName::Down));
    $controller->handle(Key::named(KeyName::Down));
    $controller->handle(Key::named(KeyName::Down));
    $this->assertSame(3, $controller->cursor());

    $controller->handle(Key::named(KeyName::Up));
    $this->assertSame(2, $controller->cursor());
  }

  public function testSubmitButton(): void {
    $controller = $this->controller();

    // Move past the 2 panels to Submit (index 2), then activate it.
    $controller->handle(Key::named(KeyName::Down));
    $controller->handle(Key::named(KeyName::Down));
    $controller->handle(Key::named(KeyName::Enter));

    $this->assertTrue($controller->isDone());
    $this->assertFalse($controller->isCancelled());
  }

  public function testCancelButton(): void {
    $controller = $this->controller();

    // Move to Cancel (index 3), then activate it.
    $controller->handle(Key::named(KeyName::Down));
    $controller->handle(Key::named(KeyName::Down));
    $controller->handle(Key::named(KeyName::Down));
    $controller->handle(Key::named(KeyName::Enter));

    $this->assertTrue($controller->isDone());
    $this->assertTrue($controller->isCancelled());
  }

  public function testButtonsRenderByDefault(): void {
    $controller = $this->controller();
    $frame = Ansi::strip($controller->frame(12));

    $this->assertStringContainsString('Submit', $frame);
    $this->assertStringContainsString('Cancel', $frame);

    // Select the Submit button and re-render: it is marked selected.
    $controller->handle(Key::named(KeyName::Down));
    $controller->handle(Key::named(KeyName::Down));
    $this->assertStringContainsString('❯ [ Submit ]', Ansi::strip($controller->frame(12)));
  }

  public function testButtonsOptOut(): void {
    $config = (new ConfigLoader())->fromArray([
      'title' => 'Demo',
      'buttons' => FALSE,
      'panels' => [['id' => 'p', 'fields' => [['id' => 'a', 'label' => 'A']]]],
    ]);
    $controller = new PanelController($config, new DarkTheme(FALSE, 40), ['a' => 'x'], []);

    $this->assertStringNotContainsString('Submit', Ansi::strip($controller->frame(12)));

    // With buttons off, the single field is the only item: Down clamps at 0.
    $controller->handle(Key::named(KeyName::Down));
    $this->assertSame(0, $controller->cursor());
  }

  public function testEditFieldReturnsWithValue(): void {
    $controller = $this->controller();
    $controller->handle(Key::named(KeyName::Enter));
    $controller->handle(Key::named(KeyName::Enter));
    $this->assertTrue($controller->isEditing());

    $controller->handle(Key::char('!'));
    $controller->handle(Key::named(KeyName::Enter));

    $this->assertFalse($controller->isEditing());
    $this->assertSame('Acme!', $controller->answers()->value('name'));
    $this->assertSame('edited', $controller->answers()->provenanceOf('name'));
  }

  public function testEditCancelKeepsValue(): void {
    $controller = $this->controller();
    $controller->handle(Key::named(KeyName::Enter));
    $controller->handle(Key::named(KeyName::Enter));
    $this->assertTrue($controller->isEditing());

    $controller->handle(Key::named(KeyName::Escape));

    $this->assertFalse($controller->isEditing());
    $this->assertSame('Acme', $controller->answers()->value('name'));
  }

  public function testDrillIntoSubPanel(): void {
    $controller = $this->controller();
    $controller->handle(Key::named(KeyName::Enter));
    $controller->handle(Key::named(KeyName::Down));
    $this->assertSame(1, $controller->cursor());

    $controller->handle(Key::named(KeyName::Enter));

    $this->assertSame('Advanced', $controller->currentPanel()->title);
  }

  public function testMouseWheelScrollsWithoutMovingCursor(): void {
    $controller = $this->controller();
    $before = $controller->cursor();

    $controller->handle(Key::named(KeyName::MouseWheelDown));

    $this->assertSame($before, $controller->cursor());
    $this->assertFalse($controller->isEditing());
    $this->assertStringContainsString('Demo', $controller->frame(4));
  }

  public function testMouseWheelUpScrollsBackWithoutMovingCursor(): void {
    $controller = $this->controller();

    $controller->handle(Key::named(KeyName::MouseWheelDown));
    $controller->handle(Key::named(KeyName::MouseWheelUp));

    $this->assertSame(0, $controller->cursor());
    $this->assertStringContainsString('Demo', $controller->frame(4));
  }

  public function testFrameShowsSelectionAndValue(): void {
    $controller = $this->controller();
    $controller->handle(Key::named(KeyName::Enter));

    $frame = Ansi::strip($controller->frame(12));

    $this->assertStringContainsString('General', $frame);
    $this->assertStringContainsString('❯ Name', $frame);
    $this->assertStringContainsString('Acme', $frame);
  }

  public function testEditingFrameShowsWidget(): void {
    $controller = $this->controller();
    $controller->handle(Key::named(KeyName::Enter));
    $controller->handle(Key::named(KeyName::Enter));

    $frame = $controller->frame(12);

    $this->assertStringContainsString('Name', $frame);
    $this->assertStringContainsString('Acme', $frame);
  }

  public function testQuit(): void {
    $controller = $this->controller();
    $this->assertFalse($controller->isDone());

    $controller->handle(Key::char('q'));

    $this->assertTrue($controller->isDone());
  }

  /**
   * A controller over a two-panel config seeded with answers.
   */
  protected function controller(): PanelController {
    $config = (new ConfigLoader())->fromArray([
      'title' => 'Demo',
      'panels' => [
        ['id' => 'general', 'title' => 'General', 'fields' => [
          ['id' => 'name', 'label' => 'Name'],
        ], 'panels' => [
          ['id' => 'adv', 'title' => 'Advanced', 'fields' => [['id' => 'debug', 'label' => 'Debug', 'type' => 'confirm']]],
        ]],
        ['id' => 'drupal', 'title' => 'Drupal', 'fields' => [['id' => 'profile', 'label' => 'Profile']]],
      ],
    ]);
    $theme = new DarkTheme(FALSE, 40);

    return new PanelController($config, $theme, ['name' => 'Acme', 'debug' => FALSE, 'profile' => 'standard'], []);
  }

}
