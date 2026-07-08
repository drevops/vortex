<?php

declare(strict_types=1);

namespace DrevOps\Tui\Render;

use DrevOps\Tui\Answers\Answers;
use DrevOps\Tui\Config\Config;
use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\Panel;
use DrevOps\Tui\Input\Key;
use DrevOps\Tui\Input\KeyName;
use DrevOps\Tui\Input\KeyParser;
use DrevOps\Tui\Theme\ThemeInterface;
use DrevOps\Tui\Widget\WidgetFactory;
use DrevOps\Tui\Widget\WidgetInterface;

/**
 * The interactive state machine behind the panel TUI.
 *
 * Holds navigation, the selection cursor, the scroll offset and the current
 * editor, and advances on one key at a time - so the whole interaction is
 * testable headlessly. Mouse-wheel scrolls without moving the cursor; a key
 * press re-engages cursor-follow. Editing a field returns to the panel with
 * the new value shown and marked "edited".
 *
 * @package DrevOps\Tui\Render
 */
class PanelController {

  /**
   * The panel navigator.
   */
  protected Navigator $navigator;

  /**
   * The selection cursor within the current panel.
   */
  protected int $cursor = 0;

  /**
   * The scroll offset of the current panel.
   */
  protected int $offset = 0;

  /**
   * Whether the viewport follows the cursor (a key press re-engages it).
   */
  protected bool $followCursor = TRUE;

  /**
   * The active field editor, if any.
   */
  protected ?WidgetInterface $editor = NULL;

  /**
   * The field being edited, if any.
   */
  protected ?Field $editing = NULL;

  /**
   * The widget factory.
   */
  protected WidgetFactory $widgets;

  /**
   * The scroller.
   */
  protected Scroller $scroller;

  /**
   * Whether the user has chosen to quit.
   */
  protected bool $done = FALSE;

  /**
   * Whether the user cancelled via the cancel button.
   */
  protected bool $cancelled = FALSE;

  /**
   * Construct a controller.
   *
   * @param \DrevOps\Tui\Config\Config $config
   *   The configuration.
   * @param \DrevOps\Tui\Theme\ThemeInterface $theme
   *   The theme (the visual authority for rendering).
   * @param array<string,mixed> $values
   *   The initial answer values (typically the engine's resolved answers).
   * @param array<string,string> $provenance
   *   The initial provenance.
   * @param string $banner
   *   An optional start banner (logo) shown before the interactive loop.
   * @param string $version
   *   An optional version string shown below the banner.
   */
  public function __construct(
    protected Config $config,
    protected ThemeInterface $theme,
    protected array $values = [],
    protected array $provenance = [],
    protected string $banner = '',
    protected string $version = '',
  ) {
    $this->widgets = new WidgetFactory();
    $this->scroller = new Scroller();
    $this->navigator = new Navigator(new Panel('hub', $config->title, '', [], $config->panels));
  }

  /**
   * Process one key press.
   *
   * @param \DrevOps\Tui\Input\Key $key
   *   The key.
   */
  public function handle(Key $key): void {
    if ($this->editor instanceof WidgetInterface) {
      $this->handleEditing($key);

      return;
    }

    $this->handleNavigation($key);
  }

  /**
   * Whether a field is being edited.
   *
   * @return bool
   *   TRUE when editing.
   */
  public function isEditing(): bool {
    return $this->editor instanceof WidgetInterface;
  }

  /**
   * Whether the user has chosen to quit.
   *
   * @return bool
   *   TRUE when done.
   */
  public function isDone(): bool {
    return $this->done;
  }

  /**
   * Whether the user cancelled.
   *
   * @return bool
   *   TRUE when the user activated the cancel button.
   */
  public function isCancelled(): bool {
    return $this->cancelled;
  }

  /**
   * Run the interactive loop against a terminal until the user quits.
   *
   * @param \DrevOps\Tui\Render\Terminal $terminal
   *   The terminal.
   *
   * @return \DrevOps\Tui\Answers\Answers
   *   The collected answers.
   */
  public function run(Terminal $terminal): Answers {
    // @codeCoverageIgnoreStart
    $parser = new KeyParser();
    $terminal->setup();

    try {
      if ($this->banner !== '') {
        $terminal->render($this->theme->renderBanner($this->banner, $this->version) . "\n\nPress any key to continue...");
        $terminal->read();
      }

      while (!$this->done) {
        // Fill the terminal, reserving four rows of chrome: the breadcrumb
        // header, the status footer and the two scroll indicators.
        $terminal->render($this->frame(max(3, $terminal->height() - 4)));
        foreach ($parser->parse($terminal->read()) as $key) {
          $this->handle($key);
        }
      }
    }
    finally {
      $terminal->restore();
      if ($this->config->clearOnExit) {
        $terminal->clear();
      }
    }

    return $this->answers();
    // @codeCoverageIgnoreEnd
  }

  /**
   * The selection cursor.
   *
   * @return int
   *   The cursor index.
   */
  public function cursor(): int {
    return $this->cursor;
  }

  /**
   * The current panel.
   *
   * @return \DrevOps\Tui\Config\Panel
   *   The current panel.
   */
  public function currentPanel(): Panel {
    return $this->navigator->current();
  }

  /**
   * The current answers.
   *
   * @return \DrevOps\Tui\Answers\Answers
   *   The self-describing answers.
   */
  public function answers(): Answers {
    return Answers::forConfig($this->config, $this->values, $this->provenance);
  }

  /**
   * Render the current frame.
   *
   * @param int $height
   *   The body viewport height.
   *
   * @return string
   *   The frame.
   */
  public function frame(int $height = 12): string {
    if ($this->editor instanceof WidgetInterface) {
      $label = $this->editing instanceof Field ? $this->editing->label : '';

      return $label . "\n" . $this->editor->view($this->theme) . "\n\n" . $this->theme->renderStatusLine();
    }

    $panel = $this->navigator->current();
    [$body, $cursor_line] = $this->theme->renderBody($panel, $this->answers(), $this->cursor);

    if ($this->buttonsVisible()) {
      $base = $this->theme->itemCount($panel);
      $selected = $this->cursor >= $base ? $this->cursor - $base : -1;
      if ($this->cursor >= $base) {
        $cursor_line = count($body);
      }
      $body[] = $this->theme->renderButtonBar([$this->config->submitLabel, $this->config->cancelLabel], $selected);
    }

    $total = count($body);

    if ($this->followCursor) {
      $viewport = $this->scroller->compute($total, $height, $cursor_line, $this->offset);
    }
    else {
      $offset = $this->scroller->scroll($this->offset, 0, $total, $height);
      $viewport = new Viewport($offset, $offset > 0, $offset + $height < $total);
    }

    $this->offset = $viewport->offset;
    $header = [$this->theme->renderBreadcrumbLine($this->navigator)];
    $footer = [$this->theme->renderStatusLine()];

    return $this->theme->renderFrame($header, $body, $footer, $viewport, $height);
  }

  /**
   * Handle a key while editing a field.
   *
   * @param \DrevOps\Tui\Input\Key $key
   *   The key.
   */
  protected function handleEditing(Key $key): void {
    if (!$this->editor instanceof WidgetInterface || !$this->editing instanceof Field) {
      // @codeCoverageIgnoreStart
      return;
      // @codeCoverageIgnoreEnd
    }

    $this->editor->handle($key);

    if ($this->editor->isComplete()) {
      $this->values[$this->editing->id] = $this->editor->value();
      $this->provenance[$this->editing->id] = 'edited';
      $this->closeEditor();
    }
    elseif ($this->editor->isCancelled()) {
      $this->closeEditor();
    }
  }

  /**
   * Handle a key while navigating a panel.
   *
   * @param \DrevOps\Tui\Input\Key $key
   *   The key.
   */
  protected function handleNavigation(Key $key): void {
    if ($key->isChar() && $key->char === 'q') {
      $this->done = TRUE;

      return;
    }

    if ($key->is(KeyName::MouseWheelUp)) {
      $this->offset = max(0, $this->offset - 1);
      $this->followCursor = FALSE;

      return;
    }

    if ($key->is(KeyName::MouseWheelDown)) {
      $this->offset++;
      $this->followCursor = FALSE;

      return;
    }

    $this->followCursor = TRUE;
    $count = $this->theme->itemCount($this->navigator->current()) + ($this->buttonsVisible() ? 2 : 0);

    if ($key->is(KeyName::Up)) {
      $this->cursor = max(0, $this->cursor - 1);
    }
    elseif ($key->is(KeyName::Down)) {
      $this->cursor = min(max(0, $count - 1), $this->cursor + 1);
    }
    elseif ($key->is(KeyName::Left) || $key->is(KeyName::Right)) {
      // The submit/cancel buttons are inline, so Left/Right moves between them.
      $base = $this->theme->itemCount($this->navigator->current());
      if ($this->buttonsVisible() && $this->cursor >= $base) {
        $this->cursor = max($base, min($count - 1, $this->cursor + ($key->is(KeyName::Right) ? 1 : -1)));
      }
    }
    elseif ($key->is(KeyName::Escape)) {
      if ($this->navigator->pop()) {
        $this->cursor = 0;
      }
    }
    elseif ($key->is(KeyName::Enter)) {
      $this->activate();
    }
  }

  /**
   * Activate the selected item: edit a field or drill into a sub-panel.
   */
  protected function activate(): void {
    $panel = $this->navigator->current();
    $field_count = count($panel->fields);

    if ($this->cursor < $field_count) {
      $this->openEditor($panel->fields[$this->cursor]);

      return;
    }

    $subpanel = $panel->panels[$this->cursor - $field_count] ?? NULL;
    if ($subpanel instanceof Panel) {
      $this->navigator->enter($subpanel);
      $this->cursor = 0;

      return;
    }

    if ($this->buttonsVisible()) {
      $this->activateButton($this->cursor - $field_count - count($panel->panels));
    }
  }

  /**
   * Whether the submit/cancel buttons are shown on the current panel.
   *
   * They live on the root panel only, so sub-panels are not cluttered with
   * global actions.
   *
   * @return bool
   *   TRUE when buttons are enabled and the navigator is at the root panel.
   */
  protected function buttonsVisible(): bool {
    return $this->config->buttons && $this->navigator->isRoot();
  }

  /**
   * Activate a submit (0) or cancel (1) button: finish, recording a cancel.
   *
   * @param int $index
   *   The button index.
   */
  protected function activateButton(int $index): void {
    $this->done = TRUE;
    $this->cancelled = $index === 1;
  }

  /**
   * Open the editor for a field.
   *
   * @param \DrevOps\Tui\Config\Field $field
   *   The field.
   */
  protected function openEditor(Field $field): void {
    $this->editing = $field;
    $this->editor = $this->widgets->create($field, $this->values[$field->id] ?? $field->default);
  }

  /**
   * Close the editor.
   */
  protected function closeEditor(): void {
    $this->editor = NULL;
    $this->editing = NULL;
  }

}
