# Customizer PRDs - building to installer parity

This folder tracks the work to make the **customizer** reach feature parity with
the current Vortex **installer** (`.vortex/installer`), delivered as a standalone,
tested package. Each feature is captured as a PRD (a requirements + acceptance
checklist), reviewed and approved before implementation, then ticked off as it is
built.

## Purpose

The interactive UX is already proven in the throwaway prototype
(`.vortex/customizer/playground/run.php`). These PRDs:

1. **Productionise** the prototype into `src/` with tests (it currently lives as
   one procedural file in `playground/`).
2. **Add** the installer behaviours the prototype does not yet cover (discovery,
   non-interactive collection, schema introspection).
3. **Map** every installer setting into the config so the panel offers the same
   choices.

## Scope boundary - the customizer *collects*, the CLI *applies*

- **In scope (customizer):** config-driven panels; all field widgets;
  conditional visibility; derived values & overrides; discovery; non-interactive
  / scripted collection; schema introspection; validation & transform; theming;
  navigation; the collected-answers output contract.
- **Out of scope (CLI / installer refactor, tracked elsewhere):** applying
  answers to files - token/fence removal, content replacement, file/JSON/`.env`
  operations - the command surface, and shipping the Vortex config. The customizer
  produces an answer set; the CLI consumes it and mutates the project.

## Assessment of the installer (the parity target)

Grounded in `.vortex/installer`. Authoritative machine-readable inventory:
`php .vortex/installer/installer.php --schema`.

- **12 sections, ~38 prompts.** General information, Drupal, Code repository,
  Environment, Hosting, Deployment, Workflow, Notifications, Continuous
  integration, Automations, Documentation, AI.
- **5 widget types:** text, select, multiselect, confirm, suggest (autocomplete).
- **Cross-cutting behaviours:**
  - **Discovery** - auto-detect values from an existing project / environment
    (`discover()` per handler; drives "update an existing project" mode).
  - **Conditional visibility** - prompts shown only when a dependency is met
    (`shouldRun()` / `dependsOn()`), plus auto-resolve-and-skip (Profile, Theme,
    Webroot) and post-submit fix-ups (custom profile/theme merge, forced values).
  - **Chained defaults** - one answer feeds another's default (name -> machine
    name -> theme, etc.).
  - **Non-interactive / scripted** - `--no-interaction`, `--prompts <json|file>`,
    per-prompt env vars, with a fixed default-resolution precedence.
  - **Schema surfaces** - `--schema`, `--validate`, `--agent-help`.
  - **Validation & transform** - per-field `validate()` and `transform()`.

## What the prototype already proves (reuse, don't reinvent)

`playground/run.php` already implements: the YAML config schema, all 5 widgets,
conditional visibility (`when`), derived values + sticky overrides, sections +
recursive sub-panels, the scrolling viewport, mouse-wheel scroll, themeable colour
roles, and the review screen. Those PRDs are about *moving proven code into `src/`
with tests* - not inventing behaviour.

## PRD index & tracking

Status: `draft` (written) -> `approved` (you sign off) -> `in-progress` -> `done`.

| PRD | Title | Prototype | Status |
|-----|-------|-----------|--------|
| [0001](PRD-0001-config-schema-and-panel-model.md) | Config schema & panel/field model | yes | draft |
| 0002 | Field widgets + validation/transform | yes | planned |
| 0003 | Conditional visibility & post-submit fix-ups | partial | planned |
| 0004 | Derived values & sticky overrides | yes | planned |
| 0005 | Discovery / environment auto-detection | no | planned |
| 0006 | Non-interactive & scripted collection | no | planned |
| 0007 | Schema introspection & agent surfaces | no | planned |
| 0008 | TUI rendering, navigation & theming | yes | planned |
| 0009 | Collected-answers model & output contract | partial | planned |
| 0010 | Vortex config parity: General information & Drupal | n/a | planned |
| 0011 | Vortex config parity: Hosting, Deployment & Workflow | n/a | planned |
| 0012 | Vortex config parity: CI, Automations, Notifications, Docs & AI | n/a | planned |

Engine capabilities: 0001-0009. Content/settings parity: 0010-0012 (these map each
installer handler -> a config field, and likely ship from the CLI, but are tracked
here to guarantee coverage).

## PRD conventions

Each PRD is a **requirements document**, not an implementation design. Sections:

- **Context** - why the feature exists, one paragraph.
- **Requirements** - a checklist of what must be true.
- **Acceptance criteria** - a checklist of observable/testable outcomes.
- **Installer references** - `file:symbol` pointers to the current behaviour.
- **Out of scope / Dependencies** - boundaries and blocking PRDs.

## Workflow

1. Drafts land here for your review.
2. You approve a PRD (flip its status to `approved`).
3. Implementation happens one approved PRD at a time (a commit/slice per PRD),
   ticking the checklist as it goes.
4. You review the implementation against the PRD's acceptance criteria.
