<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Builder;

use DrevOps\Customizer\Config\Config;
use DrevOps\Customizer\Config\ConfigException;
use DrevOps\Customizer\Config\Panel;

/**
 * A fluent builder for the Config model, declared in PHP instead of YAML.
 *
 * @package DrevOps\Customizer\Builder
 */
final class Form {

  /**
   * The theme name or class (empty for the default).
   */
  protected string $theme = '';

  /**
   * The start banner (logo).
   */
  protected string $banner = '';

  /**
   * Whether the interactive TUI shows submit and cancel buttons.
   */
  protected bool $buttons = TRUE;

  /**
   * The submit button label.
   */
  protected string $submitLabel = 'Submit';

  /**
   * The cancel button label.
   */
  protected string $cancelLabel = 'Cancel';

  /**
   * Whether to clear the screen when the interactive TUI exits.
   */
  protected bool $clearOnExit = TRUE;

  /**
   * Force ANSI colour on/off; NULL auto-detects.
   */
  protected ?bool $color = NULL;

  /**
   * Force Unicode/ASCII glyphs; NULL auto-detects.
   */
  protected ?bool $unicode = NULL;

  /**
   * The field-less processors, each an id and a weight.
   *
   * @var array<int,array{id:string,weight:int}>
   */
  protected array $processors = [];

  /**
   * The raw post-submit fix-up rules.
   *
   * @var array<int,array<array-key,mixed>>
   */
  protected array $fixups = [];

  /**
   * The top-level panel builders, in declaration order.
   *
   * @var \DrevOps\Customizer\Builder\PanelBuilder[]
   */
  protected array $panels = [];

  /**
   * Construct a form builder.
   *
   * @param string $title
   *   The application title.
   * @param string $subject
   *   The subject being configured.
   */
  protected function __construct(protected string $title, protected string $subject) {
  }

  /**
   * Create a form builder.
   *
   * @param string $title
   *   The application title.
   * @param string $subject
   *   The subject being configured.
   *
   * @return self
   *   The builder.
   */
  public static function create(string $title, string $subject = ''): self {
    return new self($title, $subject);
  }

  /**
   * Set the theme name or class.
   *
   * @param string $theme
   *   The theme name or class (empty for the default).
   *
   * @return $this
   *   The builder.
   */
  public function theme(string $theme): self {
    $this->theme = $theme;

    return $this;
  }

  /**
   * Set the start banner.
   *
   * @param string $banner
   *   The banner (logo).
   *
   * @return $this
   *   The builder.
   */
  public function banner(string $banner): self {
    $this->banner = $banner;

    return $this;
  }

  /**
   * Configure the submit and cancel buttons.
   *
   * @param bool $show
   *   Whether to show the buttons.
   * @param string $submit_label
   *   The submit button label.
   * @param string $cancel_label
   *   The cancel button label.
   *
   * @return $this
   *   The builder.
   */
  public function buttons(bool $show, string $submit_label = 'Submit', string $cancel_label = 'Cancel'): self {
    $this->buttons = $show;
    $this->submitLabel = $submit_label;
    $this->cancelLabel = $cancel_label;

    return $this;
  }

  /**
   * Set whether to clear the screen when the TUI exits.
   *
   * @param bool $clear
   *   Whether to clear on exit.
   *
   * @return $this
   *   The builder.
   */
  public function clearOnExit(bool $clear): self {
    $this->clearOnExit = $clear;

    return $this;
  }

  /**
   * Force ANSI colour on or off.
   *
   * @param bool|null $color
   *   TRUE/FALSE to force, NULL to auto-detect.
   *
   * @return $this
   *   The builder.
   */
  public function color(?bool $color): self {
    $this->color = $color;

    return $this;
  }

  /**
   * Force Unicode or ASCII glyphs.
   *
   * @param bool|null $unicode
   *   TRUE/FALSE to force, NULL to auto-detect.
   *
   * @return $this
   *   The builder.
   */
  public function unicode(?bool $unicode): self {
    $this->unicode = $unicode;

    return $this;
  }

  /**
   * Add a field-less processor.
   *
   * @param string $id
   *   The processor id (resolved to a handler).
   * @param int $weight
   *   The processing weight; lower runs earlier.
   *
   * @return $this
   *   The builder.
   */
  public function processor(string $id, int $weight = 0): self {
    $this->processors[] = ['id' => $id, 'weight' => $weight];

    return $this;
  }

  /**
   * Add a raw post-submit fix-up rule.
   *
   * @param array<array-key,mixed> $rule
   *   The raw rule, evaluated by the engine.
   *
   * @return $this
   *   The builder.
   */
  public function fixup(array $rule): self {
    $this->fixups[] = $rule;

    return $this;
  }

  /**
   * Add a top-level panel.
   *
   * @param string $id
   *   The panel id.
   * @param string $title
   *   The panel title.
   * @param \Closure $build
   *   The callback receiving the panel builder.
   *
   * @return $this
   *   The builder.
   */
  public function panel(string $id, string $title, \Closure $build): self {
    $panel = new PanelBuilder($id, $title);
    $build($panel);
    $this->panels[] = $panel;

    return $this;
  }

  /**
   * Build the immutable Config model.
   *
   * @return \DrevOps\Customizer\Config\Config
   *   The config.
   */
  public function build(): Config {
    $panels = array_map(static fn(PanelBuilder $panel): Panel => $panel->build(), $this->panels);

    $config = new Config(
      $this->title,
      $this->subject,
      $panels,
      $this->fixups,
      $this->theme,
      $this->banner,
      $this->buttons,
      $this->submitLabel,
      $this->cancelLabel,
      $this->clearOnExit,
      $this->processors,
      $this->color,
      $this->unicode,
    );

    $this->assertUniqueFieldIds($config);

    return $config;
  }

  /**
   * Assert that every field id is unique across the panel tree.
   *
   * @param \DrevOps\Customizer\Config\Config $config
   *   The built config.
   */
  protected function assertUniqueFieldIds(Config $config): void {
    $seen = [];

    foreach ($config->fields() as $field) {
      if (isset($seen[$field->id])) {
        throw new ConfigException(sprintf('Duplicate field id "%s".', $field->id));
      }

      $seen[$field->id] = TRUE;
    }
  }

}
