# Customizer PRDs - building to installer parity

This folder tracks the work to make the **customizer** reach feature parity with
the current Vortex **installer** (`.vortex/installer`), delivered as a standalone,
tested, **project-agnostic** package. Each feature is captured as a PRD (a
requirements + acceptance checklist), reviewed and approved before implementation,
then ticked off as it is built.

## Purpose

The interactive UX is already proven in the throwaway prototype
(`.vortex/customizer/playground/run.php`). These PRDs:

1. **Productionise** the prototype into `src/` with tests.
2. **Add** the installer behaviours the prototype does not yet cover (handler
   model, discovery, non-interactive collection, schema introspection).
3. Do **not** hard-code any project's questions - those come from the consumer.

## Architecture

The **customizer** is a generic engine; the **CLI** (or any consumer) is thin and
supplies the project-specific parts. Mirrors the installer's `PromptManager` +
handlers split, but cleanly separated:

- **Customizer (this package, generic):** config loader + panel/field model, the
  five widgets, conditionals, derived values & overrides, the discovery mechanism,
  non-interactive collection, schema surfaces, the TUI, the answers contract, and
  the **handler abstraction + name-based auto-discovery + lifecycle orchestration**.
  It never references any project's specifics.
- **CLI (thin, project-specific):** ships a **YAML config** (the questions and
  their panel structure) plus the concrete **handler classes** (inside its
  Customize command) that **extend** the customizer's base classes. Each handler
  carries a question's metadata **and** behaviour (`discover` / `validate` /
  `transform` / `process`), exactly like the installer's handlers today.
- **Wiring:** the customizer resolves each configured question to its handler
  **by name** (e.g. `machine_name` -> `MachineName`) from a handler namespace the
  consumer registers, and drives the lifecycle polymorphically. Reusable "default
  handlers" shipped by the customizer are a later addition.

**Scope boundary (resolved):** the customizer *orchestrates* the full lifecycle
generically - including calling handlers' `process()` - but the project-specific
work (metadata, discovery rules, file mutation) lives entirely in the consumer's
handlers. So the customizer is not "collect-only" and not Vortex-coupled: it is a
generic framework, and the CLI's handlers do the work.

## Assessment of the installer (the parity target)

Grounded in `.vortex/installer`. Authoritative machine-readable inventory:
`php .vortex/installer/installer.php --schema`.

- **12 sections, ~38 prompts** (General, Drupal, Code repository, Environment,
  Hosting, Deployment, Workflow, Notifications, CI, Automations, Documentation, AI)
  - each a **handler** class that supplies label/options/default/discover/validate/
  transform/process. In the new architecture these handlers are the CLI's, not the
  customizer's.
- **5 widget types:** text, select, multiselect, confirm, suggest.
- **Cross-cutting behaviours** (become customizer engine capabilities): discovery,
  conditional visibility + auto-resolve/skip + post-submit fix-ups, chained
  defaults, non-interactive/scripted collection, schema surfaces, validation &
  transform.

## What the prototype already proves (reuse, don't reinvent)

`playground/run.php` already implements: the YAML config schema, all 5 widgets,
conditional visibility (`when`), derived values + sticky overrides, sections +
recursive sub-panels, the scrolling viewport, mouse-wheel scroll, themeable colour
roles, and the review screen - as one procedural file. Those PRDs are about
*moving proven code into `src/` with tests*, now behind the handler abstraction.

## PRD index & tracking

Status: `draft` (written) -> `approved` (you sign off) -> `in-progress` -> `done`.

| PRD | Title | Prototype | Status |
|-----|-------|-----------|--------|
| [0001](PRD-0001-config-schema-and-panel-model.md) | Config schema & panel/field model | yes | done |
| [0010](PRD-0010-handler-model-and-autodiscovery.md) | Handler model, registry & name-based auto-discovery | no | draft |
| [0002](PRD-0002-field-widgets.md) | Field widgets + validation/transform | yes | draft |
| [0003](PRD-0003-conditional-visibility.md) | Conditional visibility & post-submit fix-ups | partial | draft |
| [0004](PRD-0004-derived-values-and-overrides.md) | Derived values & sticky overrides | yes | draft |
| [0005](PRD-0005-discovery.md) | Discovery / environment auto-detection | no | draft |
| [0006](PRD-0006-non-interactive-collection.md) | Non-interactive & scripted collection | partial | draft |
| [0007](PRD-0007-schema-introspection.md) | Schema introspection & agent surfaces | no | draft |
| [0008](PRD-0008-tui-rendering-and-theming.md) | TUI rendering, navigation & theming | yes | draft |
| [0009](PRD-0009-answers-model-and-output.md) | Collected-answers model & output contract | partial | draft |

**0001 + 0010 are foundational** (the config model and the handler abstraction);
they underpin the rest.

**Not in this folder:** the Vortex-specific handlers and their YAML config are the
**CLI's** responsibility (the CLI ships and tests them), tracked with the CLI - the
customizer stays project-agnostic and contains no Vortex handler.

## PRD conventions

Each PRD is a **requirements document**, not an implementation design:

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
