<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Config;

/**
 * The root configuration model: title, subject and a tree of panels.
 *
 * @package DrevOps\Customizer\Config
 */
final readonly class Config {

  /**
   * Construct the root config.
   *
   * @param string $title
   *   The application title.
   * @param string $subject
   *   The subject being configured (e.g. the project name).
   * @param \DrevOps\Customizer\Config\Panel[] $panels
   *   The top-level panels.
   * @param array<int,array<array-key,mixed>> $fixups
   *   Raw post-submit fix-up rules, evaluated by the engine.
   * @param string $theme
   *   The theme name or class for the interactive TUI (empty for the default).
   * @param string $banner
   *   The start banner (logo) shown before the interactive TUI (optional).
   * @param bool $buttons
   *   Whether the interactive TUI shows submit and cancel buttons.
   * @param string $submitLabel
   *   The label of the submit button.
   * @param string $cancelLabel
   *   The label of the cancel button.
   * @param bool $clearOnExit
   *   Whether to clear the screen when the interactive TUI exits.
   * @param array<int,array{id:string,weight:int}> $processors
   *   Field-less processors that always run, each an id (resolved to a handler)
   *   and a weight. Used to bookend field processing (e.g. carry ".env" first,
   *   clean up last).
   * @param bool|null $color
   *   Force ANSI colour on/off in the interactive TUI; NULL auto-detects.
   * @param bool|null $unicode
   *   Force Unicode/ASCII glyphs in the interactive TUI; NULL auto-detects.
   */
  public function __construct(
    public string $title,
    public string $subject,
    public array $panels = [],
    public array $fixups = [],
    public string $theme = '',
    public string $banner = '',
    public bool $buttons = TRUE,
    public string $submitLabel = 'Submit',
    public string $cancelLabel = 'Cancel',
    public bool $clearOnExit = TRUE,
    public array $processors = [],
    public ?bool $color = NULL,
    public ?bool $unicode = NULL,
  ) {
  }

  /**
   * Find a field by id anywhere in the panel tree.
   *
   * @param string $id
   *   The field id to find.
   */
  public function field(string $id): ?Field {
    return $this->findField($this->panels, $id);
  }

  /**
   * All fields flattened across the panel tree, in declaration order.
   *
   * @return \DrevOps\Customizer\Config\Field[]
   *   The fields.
   */
  public function fields(): array {
    $fields = [];
    $this->collectFields($this->panels, $fields);

    return $fields;
  }

  /**
   * Recursively flatten fields from panels into an accumulator.
   *
   * @param \DrevOps\Customizer\Config\Panel[] $panels
   *   Panels to walk.
   * @param \DrevOps\Customizer\Config\Field[] $fields
   *   Accumulator, populated in place.
   */
  protected function collectFields(array $panels, array &$fields): void {
    foreach ($panels as $panel) {
      foreach ($panel->fields as $field) {
        $fields[] = $field;
      }

      $this->collectFields($panel->panels, $fields);
    }
  }

  /**
   * Recursively search panels for a field by id.
   *
   * @param \DrevOps\Customizer\Config\Panel[] $panels
   *   Panels to search.
   * @param string $id
   *   The field id to find.
   */
  protected function findField(array $panels, string $id): ?Field {
    foreach ($panels as $panel) {
      foreach ($panel->fields as $field) {
        if ($field->id === $id) {
          return $field;
        }
      }

      $found = $this->findField($panel->panels, $id);
      if ($found instanceof Field) {
        return $found;
      }
    }

    return NULL;
  }

}
