<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Schema;

use DrevOps\VortexCli\Prompts\Handlers\Dotenv;
use DrevOps\VortexCli\Prompts\Handlers\Internal;
use DrevOps\VortexCli\Utils\Normalizer;

/**
 * Generates a JSON schema of all installer prompts.
 *
 * @package DrevOps\VortexCli\Schema
 */
class SchemaGenerator {

  /**
   * Constructor.
   *
   * @param array<string, \DrevOps\VortexCli\Prompts\Handlers\HandlerInterface> $handlers
   *   An associative array of handler instances keyed by handler ID.
   */
  public function __construct(
    protected array $handlers,
  ) {
  }

  /**
   * Generate schema from handlers.
   *
   * @return array<string, mixed>
   *   The schema structure with a 'prompts' key.
   */
  public function generate(): array {
    $prompts = [];

    foreach ($this->handlers as $id => $handler) {
      if (in_array($id, static::getExcludedHandlers(), TRUE)) {
        continue;
      }

      $prompts[] = [
        'id' => $handler::id(),
        'type' => $handler->type()->value,
        'label' => $handler->label(),
        'description' => $handler::description([]),
        'options' => Normalizer::normalizeOptions($handler->options([])),
        'default' => $handler->default([]),
        'required' => $handler->isRequired(),
        'depends_on' => $handler->dependsOn(),
      ];
    }

    return ['prompts' => $prompts];
  }

  /**
   * Get a list of handler IDs to exclude from the schema.
   *
   * This allows us to omit internal or non-prompt handlers from the
   * generated schema.
   *
   * @return array<string>
   *   An array of handler IDs to exclude.
   */
  public static function getExcludedHandlers(): array {
    return [
      Dotenv::id(),
      Internal::id(),
    ];
  }

}
