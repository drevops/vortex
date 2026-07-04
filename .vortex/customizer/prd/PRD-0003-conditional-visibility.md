# PRD-0003 - Conditional visibility & post-submit fix-ups

**Status:** draft · **Prototype:** partial · **Depends on:** 0001, 0002 · **Blocks:** 0004, 0006

## Context

Fields appear only when a dependency is met, some values auto-resolve and skip
their prompt, and a few dependent values are merged or forced after collection.
The prototype has the `when` predicate; the installer has richer gating and
post-submit fix-ups that must be reproduced.

## Requirements

- [ ] Structured `when` conditions: `field` with `eq` / `ne` / `in` / `contains`, composed with `all` / `any` / `not`.
- [ ] Inactive fields render disabled with a generated "appears when X = Y" reason (config may opt to fully hide instead).
- [ ] Auto-resolve-and-skip: a field whose value is fully determined is set without prompting.
- [ ] Post-submit fix-ups: merge a custom sub-value into its parent (e.g. custom profile/theme name), and force a dependent value (e.g. database source = none when provisioning from profile).
- [ ] Re-evaluate conditionals + fix-ups on every answer change until stable.

## Acceptance criteria

- [ ] Toggling a source field activates/deactivates its dependents live; tested.
- [ ] An auto-resolvable field is skipped and its value still set; tested.
- [ ] Each documented fix-up applies deterministically; tested.
- [ ] Inactive fields are excluded from the collected answer set; tested.

## Installer references

- `.vortex/installer/src/Prompts/Handlers/HandlerInterface.php` - `shouldRun()` / `dependsOn()`.
- `.vortex/installer/src/Prompts/PromptManager.php` - `addIf()` gating and post-submit fix-ups (~:253-277), `resolveOrPrompt()`.
- Prototype: `matchCond` / `isActive` / `describeCond`.

## Out of scope

Derive/override (PRD-0004).
