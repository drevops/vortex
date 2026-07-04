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
   */
  public function __construct(
    public string $title,
    public string $subject,
    public array $panels = [],
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
