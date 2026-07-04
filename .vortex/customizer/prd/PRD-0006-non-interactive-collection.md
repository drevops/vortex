# PRD-0006 - Non-interactive & scripted collection

**Status:** done · **Prototype:** partial (headless `--keys` hook) · **Depends on:** 0001, 0002, 0003, 0004, 0010 · **Blocks:** 0007

## Context

The customizer must run without a TTY so CI and agents can drive it. The prototype
has only a headless keystroke test hook; this PRD productionises a real
non-interactive path with a defined precedence.

## Requirements

- [x] `--no-interaction`: resolve every question to its default/derived/discovered value without prompting.
- [x] `--config` / `--prompts <json|file>`: answer overrides keyed by question id (highest-precedence default).
- [x] Per-question env-var overrides with a documented naming scheme; env wins.
- [x] A fixed precedence: `--prompts` > env > discovered > derived > handler/static default.
- [x] The non-interactive path never blocks on input; safe in CI.

## Acceptance criteria

- [x] `--no-interaction` yields a complete answer set from defaults; tested.
- [x] `--prompts` JSON and env overrides apply at the correct precedence; tested.
- [x] Runs headless with no TTY; tested.

## Installer references

- `Config` (`NO_INTERACTION` / `PROMPTS`), `OptionsResolver`, `PromptManager::args()` precedence (~:708-726), `envName()`.
- Prototype: scripted mode / `runKeys`.

## Out of scope

Schema / validate surfaces (PRD-0007).

## Design decisions

- **Layered config is DIY and dependency-free** (not `consolidation/config`). The customizer stays a thin, portable library, so the external input layers (static/handler default < config file < env < `--prompts`/`--config`) are merged by a small in-engine overlay helper that feeds the Engine's existing field-aware precedence (which interleaves `discovered` and `derived`). `consolidation/config` (`ConfigOverlay`/loaders/`interpolate`) was evaluated and declined for the core to avoid coupling the portable engine to a key-value/dot-notation store; only its external-layering slice overlapped, and the Engine already owns the field-aware half. A consumer app (the CLI) may still adopt it independently to assemble inputs.
