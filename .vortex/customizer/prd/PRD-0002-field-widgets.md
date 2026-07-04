# PRD-0002 - Field widgets + validation/transform

**Status:** done · **Prototype:** yes · **Depends on:** 0001 · **Blocks:** 0003, 0006, 0008

## Context

Each field is collected through one of five interactive widgets, with per-field
validation and value transformation. The prototype implements all five; this PRD
moves them into `src/` as testable components with a consistent input model.

## Requirements

- [x] **text** - single-line input: printable chars, backspace, cursor.
- [x] **select** - single-choice radio list with arrow navigation.
- [x] **multiselect** - checkbox list: space to toggle, type-to-filter, all/none.
- [x] **confirm** - yes/no toggle.
- [x] **suggest** - autocomplete filtering over a fixed option set.
- [x] Per-field `validate` (e.g. required, machine-name pattern) with an inline error and re-prompt-on-invalid.
- [x] Per-field `transform` applied to the accepted value (e.g. trim).
- [x] One shared key model across widgets: arrows, enter, esc, space, backspace, printable chars.
- [x] Each widget returns a typed value: `string` / `string[]` / `bool`.

## Acceptance criteria

- [x] Each widget is editable via scripted keystrokes and returns the expected value; unit/functional tested.
- [x] Invalid text shows the error and blocks save; a valid value saves; tested.
- [x] Multiselect filter narrows the list; toggle adds/removes; all/none work; tested.
- [x] Suggest narrows to matches and selects one; tested.

## Installer references

- `.vortex/installer/src/Prompts/PromptType.php` - widget-type enum.
- `.vortex/installer/src/Prompts/Handlers/*.php` - `validate()` / `transform()`.
- `.vortex/installer/src/Prompts/PromptManager.php` - `prompt()` / `args()` widget dispatch.
- Prototype: `renderChoice`/`renderMulti`/`renderText`/`renderConfirm` + `onChoice`/`onMulti`/`onText`/`onConfirm`.

## Out of scope

Layout/theming (PRD-0008), conditional gating (PRD-0003), derive/override (PRD-0004).
