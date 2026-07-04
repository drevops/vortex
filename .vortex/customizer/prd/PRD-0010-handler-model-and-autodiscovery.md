# PRD-0010 - Handler model, registry & name-based auto-discovery

**Status:** draft · **Prototype:** no (the prototype inlines metadata in config) · **Depends on:** 0001 · **Blocks:** 0002-0009

## Context

The customizer is a **generic, project-agnostic engine**. All project-specific
behaviour lives in **handlers** - one class per question - which the consuming
application (the CLI) provides, exactly as the current installer does. The
customizer defines the handler contract and **auto-discovers** the concrete handler
for each configured question **by name**; the CLI supplies the YAML config (the
questions and their panel structure) plus the handler classes (inside its Customize
command), extends the customizer's base classes, and stays as thin as possible. The
customizer must never reference any project's specifics.

## Requirements

- [ ] Define a handler contract in the customizer (interface + abstract base) covering a question's lifecycle: metadata (type/label/description/options/default), `discover()`, `validate()`, `transform()`, and `process()` (apply the answer).
- [ ] The CLI's handlers **extend** the customizer's base classes to implement concrete behaviour.
- [ ] **Name-based auto-discovery**: resolve a configured question id/name to its handler class by convention (e.g. `machine_name` -> `MachineName`) from one or more handler namespaces/directories registered by the consumer.
- [ ] A handler registry the engine queries; an unresolved handler raises an actionable error (a configurable **default handler** is a later addition).
- [ ] The engine orchestrates the lifecycle **generically** - discover -> collect (widget) -> validate/transform -> process - calling handler methods polymorphically without knowing their project meaning.
- [ ] Config supplies structure (panels, ordering, which questions) and may override handler-provided metadata; handlers supply behaviour. The exact metadata split between config and handler is an **open design point** - keep both possible.
- [ ] The customizer ships **zero** project-specific handlers; reusable "default handlers" referenced from config are a later addition.

## Acceptance criteria

- [ ] A question id in config resolves to a consumer-provided handler class by name; tested against a fixture handler namespace.
- [ ] The engine invokes discover/validate/transform/process on the resolved handler; tested with a spy handler.
- [ ] A missing handler raises an error naming the offending question id; tested.
- [ ] The customizer package contains no Vortex/project-specific handler; enforced by tests (only abstractions + fixtures).

## Installer references

- `.vortex/installer/src/Prompts/Handlers/HandlerInterface.php`, `AbstractHandler.php` - the contract and id-from-classname derivation.
- `.vortex/installer/src/Prompts/PromptManager.php` - `initHandlers()` (auto-discovery), `runProcessors()` (process orchestration).

## Out of scope

The Vortex handler set + its config (CLI-side, tracked in the CLI). The processing
implementations themselves (they live in the CLI's handlers).

## Open design points (resolve during review)

- How much question metadata lives in config vs the handler.
- How reusable "default handlers" are referenced from config (future "update customizer" work).
