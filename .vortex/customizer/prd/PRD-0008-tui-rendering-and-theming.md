# PRD-0008 - TUI rendering, navigation & theming

**Status:** done · **Prototype:** yes · **Depends on:** 0001, 0002, 0003, 0004 · **Blocks:** -

## Context

The full-screen panel UX: recursive panel navigation, internal scrolling, mouse-
wheel scroll, the review screen, and a configurable colour theme. The prototype
implements all of it; this PRD moves it into `src/` with tests and headless frame
checks.

## Requirements

- [x] Alternate-screen takeover with an internal scrolling viewport (pinned header/footer, cursor-follow, ▲/▼ indicators) - native scrollback is disabled in this mode.
- [x] Mouse-wheel scrolls the panel without moving the cursor; a key press re-engages cursor-follow.
- [x] Recursive navigation (hub -> panel -> sub-panel; breadcrumb; esc pops).
- [x] Review screen (grouped, nested) + apply hand-off.
- [x] Config-driven colour theme: semantic roles (label/value/marker/badge/...) -> styles; presets; per-role overrides; `--no-color` safe.
- [x] Provenance badges right-aligned by visible width (ANSI-stripped).
- [x] Robust key input (arrows, pgup/pgdn/home/end, esc-vs-arrow disambiguation); terminal restored on exit/signal.

## Acceptance criteria

- [x] Panels scroll and keep the cursor visible; ▲/▼ indicators correct; verified via headless frame probes.
- [x] Editing a field returns to the panel with the value shown; sub-panel navigation works; tested.
- [x] Theme switches via preset/override; layout stays aligned with colour off; verified.

## Installer references

- `.vortex/installer/src/Utils/Tui.php`, `InstallerPresenter.php`.
- Prototype: `renderPanel` / `composeFrame` / scrolling + the theme system.

## Out of scope

Widget internals (PRD-0002).
