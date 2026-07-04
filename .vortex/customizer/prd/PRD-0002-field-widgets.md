# PRD-0002 - Field widgets + validation/transform

**Status:** draft · **Prototype:** yes · **Depends on:** 0001 · **Blocks:** 0003, 0006, 0008

## Context

Each field is collected through one of five interactive widgets, with per-field
validation and value transformation. The prototype implements all five; this PRD
moves them into `src/` as testable components with a consistent input model.

## Requirements

- [ ] **text** - single-line input: printable chars, backspace, cursor.
- [ ] **select** - single-choice radio list with arrow navigation.
- [ ] **multiselect** - checkbox list: space to toggle, type-to-filter, all/none.
- [ ] **confirm** - yes/no toggle.
- [ ] **suggest** - autocomplete filtering over a fixed option set.
- [ ] Per-field `validate` (e.g. required, machine-name pattern) with an inline error and re-prompt-on-invalid.
- [ ] Per-field `transform` applied to the accepted value (e.g. trim).
- [ ] One shared key model across widgets: arrows, enter, esc, space, backspace, printable chars.
- [ ] Each widget returns a typed value: `string` / `string[]` / `bool`.

## Acceptance criteria

- [ ] Each widget is editable via scripted keystrokes and returns the expected value; unit/functional tested.
- [ ] Invalid text shows the error and blocks save; a valid value saves; tested.
- [ ] Multiselect filter narrows the list; toggle adds/removes; all/none work; tested.
- [ ] Suggest narrows to matches and selects one; tested.

## Installer references

- `.vortex/installer/src/Prompts/PromptType.php` - widget-type enum.
- `.vortex/installer/src/Prompts/Handlers/*.php` - `validate()` / `transform()`.
- `.vortex/installer/src/Prompts/PromptManager.php` - `prompt()` / `args()` widget dispatch.
- Prototype: `renderChoice`/`renderMulti`/`renderText`/`renderConfirm` + `onChoice`/`onMulti`/`onText`/`onConfirm`.

## Out of scope

Layout/theming (PRD-0008), conditional gating (PRD-0003), derive/override (PRD-0004).
