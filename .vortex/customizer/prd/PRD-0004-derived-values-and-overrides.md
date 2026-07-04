# PRD-0004 - Derived values & sticky overrides

**Status:** draft · **Prototype:** yes · **Depends on:** 0001, 0002, 0003 · **Blocks:** -

## Context

A field's value can be computed from another and follow it as it changes; the
moment the user edits it, it becomes a pinned override that stops following;
resetting relinks it. This mirrors the installer's chained defaults but makes the
"pinned vs following" state explicit and visible. The prototype implements it.

## Requirements

- [ ] `derive` rule: a template with `{field}` interpolation plus an optional per-value transform (`machine` / `host` / `lower` / `upper`).
- [ ] Fixpoint recomputation so chains settle (name -> machine name -> theme).
- [ ] Editing a derived field marks it overridden; the override survives later changes to its source.
- [ ] Reset (per field and per section) clears overrides and re-derives.
- [ ] Provenance badge on the label line: plain `auto` (following) vs a filled reverse-video `override` block (pinned), with a selected-row footnote explaining what it no longer follows.

## Acceptance criteria

- [ ] Override a derived field, then change its source: the override holds while following fields update; verified headlessly.
- [ ] Reset relinks a previously overridden field; tested.
- [ ] Badges/footnote reflect auto vs override correctly; verified.
- [ ] Chained derivations settle deterministically; tested.

## Installer references

- `.vortex/installer/src/Prompts/Handlers/*.php` - chained `default($responses)` (Name -> MachineName -> Domain/Theme/images).
- Prototype: `recompute` / `computeDerived` / `applyTransform` / `badgeFor` + the `derive:` config and the `auto`/`override` badge roles.

## Out of scope

Widget rendering (PRD-0002, PRD-0008).
