# PRD-0009 - Collected-answers model & output contract

**Status:** draft · **Prototype:** partial · **Depends on:** 0001, 0006, 0007, 0010 · **Blocks:** -

## Context

The customizer's product is a validated answer set plus a summary. Handlers'
`process()` consume it (PRD-0010) and callers can consume it as JSON. This PRD
defines that contract.

## Requirements

- [ ] An answers model keyed by question id, typed per field, carrying provenance (default / derived / override / detected / edited).
- [ ] A human summary grouped by panel (review screen + post-run output).
- [ ] Machine output: emit the final answer set as JSON.
- [ ] Only active (conditional-passing) questions are included.
- [ ] A stable contract: question ids + value types are the interface handlers and callers depend on.

## Acceptance criteria

- [ ] After a run, the emitted set contains every active question with the correct type + provenance; tested.
- [ ] The JSON output validates against the schema (PRD-0007); tested.

## Installer references

- `.vortex/installer/src/Prompts/PromptManager.php` - `$responses` / `getResponses()` / `getResponsesSummary()`.
- Prototype: `dumpAnswers` / review screen.

## Out of scope

Applying answers - that is the handler `process()` orchestration (PRD-0010).
