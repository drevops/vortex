<?php

declare(strict_types=1);

namespace DrevOps\Tui\Builder;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Panel;

/**
 * A fluent builder for a Panel and its fields and sub-panels.
 *
 * @package DrevOps\Tui\Builder
 */
final class PanelBuilder {

  /**
   * The panel description.
   */
  protected string $description = '';

  /**
   * The field builders, in declaration order.
   *
   * @var \DrevOps\Tui\Builder\FieldBuilder[]
   */
  protected array $fields = [];

  /**
   * The nested panel builders, in declaration order.
   *
   * @var \DrevOps\Tui\Builder\PanelBuilder[]
   */
  protected array $panels = [];

  /**
   * Construct a panel builder.
   *
   * @param string $id
   *   The unique panel id.
   * @param string $title
   *   The panel title.
   */
  public function __construct(protected string $id, protected string $title) {
  }

  /**
   * Set the panel description.
   *
   * @param string $description
   *   The description.
   *
   * @return $this
   *   The builder.
   */
  public function description(string $description): self {
    $this->description = $description;

    return $this;
  }

  /**
   * Add a text field.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function text(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Text);
  }

  /**
   * Add a select field.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function select(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Select);
  }

  /**
   * Add a multi-select field.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function multiselect(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::MultiSelect);
  }

  /**
   * Add a confirm field.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function confirm(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Confirm);
  }

  /**
   * Add a suggest field.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function suggest(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Suggest);
  }

  /**
   * Add a number field.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function number(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Number);
  }

  /**
   * Add a textarea field.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function textarea(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Textarea);
  }

  /**
   * Add a password field.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function password(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Password);
  }

  /**
   * Add a search field (single choice with type-to-filter).
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function search(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Search);
  }

  /**
   * Add a multi-search field (multi-select with a visible search line).
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function multisearch(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::MultiSearch);
  }

  /**
   * Add a pause field (an acknowledgement gate).
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  public function pause(string $id, string $label = ''): FieldBuilder {
    return $this->field($id, $label, FieldType::Pause);
  }

  /**
   * Add a nested sub-panel.
   *
   * @param string $id
   *   The sub-panel id.
   * @param string $title
   *   The sub-panel title.
   * @param \Closure $build
   *   The callback receiving the sub-panel builder.
   *
   * @return $this
   *   The builder.
   */
  public function panel(string $id, string $title, \Closure $build): self {
    $panel = new self($id, $title);
    $build($panel);
    $this->panels[] = $panel;

    return $this;
  }

  /**
   * Build the immutable Panel.
   *
   * @return \DrevOps\Tui\Config\Panel
   *   The panel.
   */
  public function build(): Panel {
    return new Panel(
      $this->id,
      $this->title,
      $this->description,
      array_map(static fn(FieldBuilder $field): Field => $field->build(), $this->fields),
      array_map(static fn(PanelBuilder $panel): Panel => $panel->build(), $this->panels),
    );
  }

  /**
   * Create, register and return a field builder of a given type.
   *
   * @param string $id
   *   The field id.
   * @param string $label
   *   The label (defaults to the id).
   * @param \DrevOps\Tui\Config\FieldType $type
   *   The widget type.
   *
   * @return \DrevOps\Tui\Builder\FieldBuilder
   *   The field builder.
   */
  protected function field(string $id, string $label, FieldType $type): FieldBuilder {
    $field = new FieldBuilder($id, $label === '' ? $id : $label, $type);
    $this->fields[] = $field;

    return $field;
  }

}
