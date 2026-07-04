# PRD-0007 - Schema introspection & agent surfaces

**Status:** done · **Prototype:** no · **Depends on:** 0001, 0006, 0010 · **Blocks:** -

## Context

Machine-readable surfaces so tooling and AI agents can drive the customizer: emit
the schema, validate an answer set, and print agent guidance. Matches the
installer's `--schema` / `--validate` / `--agent-help`.

## Requirements

- [x] `--schema`: JSON of every question (id, type, label, description, options, default, required, `when`/`depends_on`, `derive`) assembled from config + handlers.
- [x] `--validate <json>`: validate an answer set (types, options, required, conditionals) with actionable errors.
- [x] `--agent-help`: instructions for driving the customizer non-interactively.
- [x] Stable schema shape, compatible with the installer's `SchemaGenerator` so existing tooling keeps working.

## Acceptance criteria

- [x] `--schema` lists every question with correct attributes; round-trip tested.
- [x] `--validate` accepts valid sets and rejects each invalid class with a clear message; tested.

## Installer references

- `.vortex/installer/src/Schema/SchemaGenerator.php`, `SchemaValidator.php`, `AgentHelp.php`.

## Out of scope

None.
