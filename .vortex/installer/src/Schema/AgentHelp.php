<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Schema;

/**
 * Renders AI agent instructions for the installer.
 *
 * @package DrevOps\VortexInstaller\Schema
 */
class AgentHelp {

  /**
   * Render agent help text.
   *
   * @return string
   *   The agent help text.
   */
  public static function render(): string {
    return <<<'AGENT_HELP'
# Vortex Installer - AI Agent Instructions

You are interacting with the Vortex installer, a CLI tool that sets up Drupal
projects from the Vortex template. This guide explains how to use the installer
programmatically.

## Workflow

1. **Discover prompts**: Run with `--schema` to get a JSON manifest of all
   available configuration prompts, their types, valid values, defaults, and
   dependencies.

2. **Build a config**: Using the schema, construct a JSON object where keys are
   either prompt IDs (e.g., `hosting_provider`) or environment variable names
   (e.g., `VORTEX_INSTALLER_PROMPT_HOSTING_PROVIDER`). Set values according to
   the prompt types and allowed options from the schema.

3. **Validate the config**: Run with `--validate --config='<json>'` to check
   your config without performing an installation. The output is a JSON object
   with `valid`, `errors`, `warnings`, and `resolved` fields.

4. **Install**: Run with `--no-interaction --config='<json>' --destination=<dir>`
   to perform the actual installation using your validated config.

## Commands

```bash
# Get the prompt schema
php installer.php --schema

# Validate a config (JSON string)
php installer.php --validate --config='{"name":"My Project","hosting_provider":"lagoon"}'

# Validate a config (JSON file)
php installer.php --validate --config=config.json

# Install non-interactively
php installer.php --no-interaction --config='<json>' --destination=./my-project
```

## Schema Format

The `--schema` output contains a `prompts` array. Each prompt has:

- `id`: The prompt identifier (use as config key).
- `env`: The environment variable name (alternative config key).
- `type`: One of `text`, `select`, `multiselect`, `confirm`, `suggest`.
- `label`: Human-readable label.
- `description`: Optional description text.
- `options`: For `select`/`multiselect`, an array of `{value, label}` objects
  representing the allowed values.
- `default`: The default value if not provided.
- `required`: Whether the prompt requires a value.
- `depends_on`: Dependency conditions. If set, this prompt only applies when
  the referenced prompt has one of the specified values. A `_system` key
  indicates a system-state dependency (not config-based).

## Value Types by Prompt Type

- `text` / `suggest`: string value.
- `select`: string value matching one of the option values.
- `multiselect`: array of strings, each matching an option value.
- `confirm`: boolean (`true` or `false`).

## Dependencies

Some prompts depend on other prompts. For example, `hosting_project_name`
depends on `hosting_provider` being `lagoon` or `acquia`. If you set
`hosting_provider` to `none`, you do not need to provide `hosting_project_name`.

When a dependency is not met:
- Omitting the dependent value is OK (it will be skipped).
- Providing a value triggers a warning (it will be ignored).

When a dependency is met:
- Required prompts must have a value or they produce an error.

## Validation Output

The `--validate` output contains:

- `valid`: boolean - whether the config is valid.
- `errors`: array of `{prompt, message}` objects for invalid values.
- `warnings`: array of `{prompt, message}` objects for ignored values.
- `resolved`: object with the final merged config (your values + defaults).

## Tips

- Start with `--schema` to understand what prompts exist.
- Provide values only for prompts you want to customize; defaults will be
  used for the rest.
- Use `--validate` to check your config before installing.
- The `resolved` field in validation output shows the complete config that
  would be used, including defaults.
AGENT_HELP;
  }

}
