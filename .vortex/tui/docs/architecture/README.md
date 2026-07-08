# How the TUI works

This is a walkthrough of the `drevops/tui` engine - what you assemble to build a form, and what happens when it runs. The diagrams are rendered from the PlantUML sources in this directory by the [`render-tui-diagrams`](../../.claude/skills/render-tui-diagrams/SKILL.md) skill; everything below is derived from `src/`, so if the prose and the code disagree, the code wins.

## The shape of it

At the centre is the **Engine**. Everything else is either something you hand it (a configuration, a set of handlers, a theme) or something it produces (validated answers, a JSON schema). The packages below mirror the `src/` subdirectories, and the arrows are the main dependencies.

![Component architecture](architecture.svg)

Read it in three bands:

- **Left - what you provide.** A **Config** (assembled by the fluent `Form` builder into `Config` -> `Panel` -> `Field`) and, optionally, **Handlers** (classes that carry behaviour). Together these declare the questions and how each one behaves.
- **Middle - the Engine and its helpers.** The Engine drives collection, leaning on `InputResolver` (read a payload), `Discovery` (detect from the directory), `Deriver` + `Transform` (compute values), and `ConditionEvaluator` (decide what is shown).
- **Right - what comes out, and how it is shown.** `Answers` (plus a `SchemaGenerator` / `SchemaValidator` for agents and forms), and the **interactive TUI** - `PanelController` composing a `Theme`, widgets, a `Navigator` and a `Terminal`.

## Step 1 - describe the questions

You declare the questions in PHP with the fluent `Form` builder: panels holding fields. A field has an `id`, a `type` (text, select, multiselect, suggest, confirm) and optional rules - `default`, `required`, `options`, `when` (show it only when a condition holds), `derive` (compute it from other fields) and `discover` (detect it from the target directory). The builder validates the declaration - rejecting duplicate field ids - and builds the immutable `Config` model. Nothing runs yet; this is pure description.

## Step 2 - attach behaviour where you need it

Most fields need no code. When one does - a dynamic default, discovery, validation or a normalisation - declare it on the field itself: `->default(fn ...)`, `->validate(fn ...)`, `->transform(fn ...)`, `->discover(...)`. A handler class named after the field id (`machine_name` -> `MachineName`) in a registered namespace offers the same four hooks as a fallback for consumers who prefer per-field classes; the field declaration wins when both exist.

## Step 3 - collect the answers

`Engine::collect()` turns the config plus whatever the caller supplied into a settled set of answers. This is the heart of the engine:

![Headless collection](dataflow-collect.svg)

Walking the sequence:

1. **Resolve each field's starting value**, in priority order: an explicit input (from `--prompts` or the environment, via `InputResolver`) beats a discovered value (in update mode), which beats a handler's dynamic `default()`, which beats the static default in the config.
2. **Settle the derived and conditional fields.** `Deriver` recomputes `derive` values (with `Transform`) until they stop changing, `ConditionEvaluator` decides which fields are active from their `when` rules, and fix-ups reconcile dependents - repeated until the whole set is stable.
3. **Validate and transform** every active field through its handler.
4. **Emit `Answers`** - the values plus their provenance (default, discovered, edited).

The same lifecycle runs whether the caller is a human at the TUI or a script passing JSON, which is why the engine is testable without a terminal.

## Step 4 - let a person answer (optional)

For interactive use, `PanelController::run()` seeds itself with the engine's resolved answers and drives a panel TUI until the user is done:

![Interactive panel TUI](dataflow-tui.svg)

Each turn it asks the **Theme** to compose a frame (the theme owns colours, glyphs and layout), computes the visible window with the `Navigator` and `Scroller`, and renders it to the `Terminal`. A key press is parsed by `KeyParser`; the controller either moves the cursor / drills into a sub-panel, or opens a widget to edit a field. Editing writes the new value back and marks it "edited". When the user finishes, it returns the same `Answers` object the headless path produces.

## Step 5 - apply the answers (the consumer's job)

Collecting produces answers; acting on them - writing files, renaming directories - is the consumer's job, never the engine's. A consumer that processes answers defines its own contract extending `HandlerInterface` with a `process()` hook, so one handler class carries a field's collection behaviour and its side effects, and orders the work by the per-field `weight` metadata (ties in reverse declaration order). This is exactly what the Vortex CLI does with its `ProcessorInterface` and `Processor`.

## Regenerating this document

The diagrams are PlantUML (`.puml`) rendered to `.svg`. After editing a source, re-render and keep this walkthrough in step with any structural change:

    plantuml -tsvg docs/architecture/*.puml

The [`render-tui-diagrams`](../../.claude/skills/render-tui-diagrams/SKILL.md) skill covers rendering, adding a new data-flow diagram, and keeping this walkthrough current.
