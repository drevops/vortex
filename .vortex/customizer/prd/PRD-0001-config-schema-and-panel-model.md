# PRD-0001 - Config schema & panel/field model

**Status:** draft · **Prototype:** yes · **Depends on:** none · **Blocks:** 0002-0009

## Context

The customizer is config-driven: a YAML file declares the panels, fields, options,
defaults and rules, and the engine renders and collects against that model. The
installer hard-codes this as `PromptManager` plus one handler class per prompt; the
customizer externalises it to config so the same engine can serve any project (the
Vortex config ships from the CLI). The prototype (`playground/run.php`) already
proves the model with a hand-rolled mini-YAML parser; this PRD turns that into a
production loader and typed model in `src/`, with tests.

## Requirements

- [ ] Parse a YAML config with a maintained library (not the prototype's hand-rolled parser).
- [ ] Produce a normalized, typed in-memory model: `title`, `subject`, and an ordered tree of panels.
- [ ] Panel shape: `id`, `title`, `description`, ordered `fields[]`, and optional nested `panels[]` (recursive sub-panels to arbitrary depth).
- [ ] Field shape: `id`, `label`, `description`, `type`, `default`, `options[]` (each `value` / `label` / `description`), and optional `required`, `machine`, `when`, `derive`.
- [ ] Model the 5 field types as a typed enum: `text`, `select`, `multiselect`, `confirm`, `suggest`.
- [ ] Preserve config declaration order everywhere (panels, fields, options).
- [ ] Resolve initial values from `default` (arrays for multiselect, bool for confirm, string otherwise).
- [ ] Fail with typed, actionable exceptions on malformed config (missing `id`, unknown `type`, malformed `option`, duplicate field id).

## Acceptance criteria

- [ ] A valid YAML fixture loads into a model exposing every panel/field/option attribute; asserted by unit tests.
- [ ] A 2-level sub-panel fixture nests correctly and is navigable by the model API.
- [ ] Each malformed-config fixture raises the expected typed exception with a message naming the offending path; tested.
- [ ] Field ids are unique across the whole tree; a duplicate raises; tested.
- [ ] Loader + model meet the package's coverage standard (per `phpunit.xml`).

## Installer references

- `.vortex/installer/src/Prompts/PromptManager.php` - section/field assembly and ordering.
- `.vortex/installer/src/Prompts/Handlers/*.php` - per-field metadata (label, options, default).
- `.vortex/installer/src/Prompts/PromptType.php` - the widget-type enum.
- `.vortex/installer/src/Schema/SchemaGenerator.php` - the introspectable schema shape to stay compatible with.
- Prototype: `.vortex/customizer/playground/run.php` - `build_sections()`, `yaml_load()`, `normalize_panel()`, `load_configs()`.

## Out of scope

Rendering and navigation (PRD-0008), widget interaction & validation execution
(PRD-0002), conditional evaluation (PRD-0003), derive/override (PRD-0004), and
applying answers to files (CLI).
