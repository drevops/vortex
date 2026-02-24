# Vortex Template Maintenance Guide

> **⚠️ MAINTENANCE MODE**: For **maintaining the Vortex template itself**.
> For **Drupal projects**, see `../CLAUDE.md`

## Project Structure

```text
vortex/
├── .vortex/                    # Test harness (removed on install)
│   ├── docs/                   # Documentation website
│   ├── installer/              # Template installer
│   ├── tests/                  # Template tests
│   └── tooling/                # Workflow scripts
└── [root files]                # The actual Drupal template
```

**Key Principle**: Outside `.vortex/` = template for users. Inside `.vortex/` =
test harness.

## Subsystems

| System       | Technology               | Purpose                |
|--------------|--------------------------|------------------------|
| `docs/`      | Docusaurus, Jest         | vortextemplate.com     |
| `installer/` | Symfony Console, PHPUnit | Template customization |
| `tests/`     | PHPUnit, BATS            | Template testing       |
| `tooling/`   | PHP                      | Workflow scripts       |

Each has its own CLAUDE.md with detailed guidance. Read when working on that
subsystem:

- `.vortex/docs/CLAUDE.md` - Documentation system
- `.vortex/installer/CLAUDE.md` - Installer, fixtures, tokens
- `.vortex/tests/CLAUDE.md` - BATS, PHPUnit, shell scripts

## Quick Commands

```bash
cd .vortex
ahoy install # Install all dependencies
ahoy update-snapshots # Update fixtures (REQUIRES PERMISSION)
ahoy lint-scripts # Lint shell scripts
ahoy update-docs # Regenerate docs from scripts
ahoy lint-markdown # Lint markdown files
```

## Cross-System Workflow

When updating template scripts:

1. Modify script in `scripts/vortex/` or `scripts/custom/`
2. Run `ahoy lint-scripts`
3. Run `ahoy update-docs`
4. Update BATS tests in `.vortex/tests/bats/`
5. Run `ahoy update-snapshots` (requires permission)

## Environment Variables

| Variable              | Purpose                |
|-----------------------|------------------------|
| `VORTEX_DEBUG=1`      | Debug mode in scripts  |
| `TEST_VORTEX_DEBUG=1` | Debug output in tests  |
| `UPDATE_SNAPSHOTS=1`  | Enable fixture updates |

## AI Assistant Guidelines

### Commands Requiring Permission

**NEVER run without explicit user permission**:

- `ahoy update-snapshots`
- `UPDATE_SNAPSHOTS=1 ./vendor/bin/phpunit`
- Any `UPDATE_SNAPSHOTS=1` command

These modify many files and take 10-15 minutes.

### Key Restrictions

- **NEVER** modify `.vortex/installer/tests/Fixtures/` directly
- American English spelling in documentation
- Sentence case for doc headings (capitalize proper nouns only)

### Coding Standards

- **Code**: Single lines preferred, no character limit
- **Comments**: Wrap at 80-120 characters
- Multi-line OK for: many parameters, arrays, chained methods, complex
  conditionals
