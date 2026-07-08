<?php

declare(strict_types=1);

namespace DrevOps\Tui\Schema;

use DrevOps\Tui\Config\Config;

/**
 * Produces instructions for driving the form non-interactively.
 *
 * @package DrevOps\Tui\Schema
 */
class AgentHelp {

  /**
   * Construct the help generator.
   *
   * @param \DrevOps\Tui\Config\Config $config
   *   The configuration to describe.
   * @param string $env_prefix
   *   The prefix for per-question env variable names (e.g. "VORTEX_").
   */
  public function __construct(protected Config $config, protected string $env_prefix = '') {
  }

  /**
   * Generate the agent help text.
   *
   * @return string
   *   The instructions.
   */
  public function generate(): string {
    $lines = [
      'Drive the form non-interactively:',
      '',
      '- Pass --no-interaction to resolve every question from defaults, discovery and derivation without prompting.',
      '- Pass --prompts with a JSON object (or a path to a JSON file) of answers keyed by question id; these take the highest precedence.',
    ];

    if ($this->env_prefix !== '') {
      $lines[] = sprintf('- Set per-question environment variables named %s<ID> (the uppercased question id); these win over discovery but lose to --prompts.', $this->env_prefix);
    }

    $lines[] = '- Precedence: --prompts > environment > discovered > derived > default.';
    $lines[] = '';
    $lines[] = 'Questions:';

    foreach ($this->config->fields() as $field) {
      $required = $field->required ? ' (required)' : '';
      $lines[] = sprintf('  %s [%s]%s - %s', $field->id, $field->type->value, $required, $field->label);
    }

    return implode("\n", $lines);
  }

}
