# Vortex Tooling Package Guide

> The `drevops/vortex-tooling` Composer package. Ships the shell scripts that
> consumer Drupal projects install into `vendor/drevops/vortex-tooling/src/`.

## Layout

```text
.vortex/tooling/
├── composer.json    # Package definition
├── package.json     # Test dependencies (bats-helpers)
├── yarn.lock
├── src/             # Shipped shell scripts (installed via Composer)
├── tests/           # BATS unit tests
│   ├── _helper.bash
│   ├── fixtures/
│   └── unit/
└── playground/      # Manual integration scripts (not shipped)
```

`tests/` and `playground/` are stripped from the package archive via
`.gitattributes` `export-ignore`.

## Tests

### BATS Unit Tests

Located in `tests/unit/`. They exercise individual shipped shell scripts with
mocked external commands.

```bash
cd .vortex
ahoy install            # Install node dependencies (run once)
ahoy test-bats          # Run all BATS tests
```

When updating a script in `src/`:

1. Update or add the matching `*.bats` test in `tests/unit/`
2. Run `ahoy lint-scripts`
3. Run `ahoy test-bats`

### Manual Integration Scripts (Playground)

Located in `playground/`. These are NOT automated tests - they send real
notifications to real external services (Slack, JIRA, New Relic, etc.) for
manual verification during development.

See `playground/README.md` for usage and required credentials.

## Shell Script Patterns

```bash
#!/usr/bin/env bash
# Environment loading
t=$(mktemp) && export -p >"${t}" && set -a && . ./.env && set +a && . "${t}" && rm "${t}"

set -eu
[ "${VORTEX_DEBUG-}" = "1" ] && set -x

# Variables with defaults
VAR="${VAR:-default}"

# Helpers
info() { printf "[INFO] %s\n" "${1}"; }
task() { printf "    > %s\n" "${1}"; }
note() { printf "      %s\n" "${1}"; }

# Main execution
```

## Package Publishing

Version is injected at publish time - never hardcode `version` in
`composer.json`. The path repository in the template's root `composer.json`
declares `"versions": {"drevops/vortex-tooling": "1.0.0"}` so the in-repo
copy resolves during dev. The installer strips that path-repo entry from
consumer sites so they resolve from Packagist.
