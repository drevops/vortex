# PRD-0005 - Discovery / environment auto-detection

**Status:** draft · **Prototype:** no · **Depends on:** 0001, 0004, 0010 · **Blocks:** 0006

## Context

When run against an existing project ("update" mode), the customizer pre-fills
answers by inspecting the destination project and environment - `.env` values,
`composer.json`, `docker-compose.yml`, presence of files/directories. This is the
installer's `discover()` mechanism and is not yet in the prototype (only a
placeholder `auto`/update badge exists).

## Requirements

- [ ] Discovery is a method on the handler contract (`discover()`, PRD-0010); the customizer provides the *mechanism* that invokes it and applies the result - never the project-specific rules.
- [ ] Optional config-declared discovery shortcuts (dotenv key, JSON path, file/dir exists, directory scan) for simple cases and future reusable default handlers.
- [ ] Detected values seed the field default (below a `--prompts` override, above a derived value or static default).
- [ ] An update-mode flag that enables discovery; fresh installs discover nothing.
- [ ] A `detected` provenance badge, distinct from `auto` (derived), `override`, and `edited`.

## Acceptance criteria

- [ ] Against a fixture project, discoverable fields are pre-filled and badged `detected`; tested.
- [ ] On a clean directory, discovery returns nothing (fresh install); tested.
- [ ] Precedence holds: `--prompts` > discovered > derived > static default; tested.

## Installer references

- `.vortex/installer/src/Prompts/Handlers/HandlerInterface.php` - `discover()`.
- Per-handler `discover()` - e.g. `HostingProvider`, `Name`, `Services`, `Webroot`.
- `.vortex/installer/src/Utils/Env.php`, `Config::isVortexProject()`.
- Prototype: the `--update` placeholder `auto` badge (to be replaced by real discovery).

## Out of scope

The Vortex-specific discovery rule per field (captured in the parity PRDs 0010-0012).

## Dependencies

Blocked by 0001; interacts with 0004 (provenance precedence) and 0006 (precedence in non-interactive mode).
