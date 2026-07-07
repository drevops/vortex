# Vortex CLI - command surface and flow

The reference for what the `vortex` binary is and how a user drives it. It is a single interactive tool, not a wizard and not a broad multi-command platform.

## One-screen principle

`vortex` presents **one interactive screen** - the customizer panels plus action buttons. There are no multi-step wizard screens. Version selection, the questions, and the actions all live on that one screen. Once you submit, the tool runs a processing pass and prints a completion summary; those are run output, not further interactive screens.

The actions live on the screen (rather than as wizard steps) precisely so more can be added later - e.g. an `Apply to remote` button - without changing the shape of the flow.

## Command surface

| Command | What it does | Opens |
|---|---|---|
| `vortex` | No operation given: auto-detect and route (see below). | the one screen, in the detected mode |
| `vortex install [<dir>]` | Materialize a fresh project into an empty directory. | the one screen, fresh answers, version = latest |
| `vortex configure` | Configure the current project on its current version. | the one screen, pre-filled, version locked |
| `vortex update [--to <version>]` | Move the current project to another template version. | the one screen, version selector focused |
| `vortex doctor` | Read-only environment and project health checks. | plain output, no screen |

`install`, `configure`, and `update` are the same operation under three names - they differ only by their inputs (see State model). They exist as explicit verbs for muscle memory and scripting; a person rarely needs them because bare `vortex` picks the right one.

## Routing when no operation is given

Running `vortex` with no operation resolves the target repository (the current directory, or `--path <dir>`) and routes automatically:

- **Empty directory / no Vortex manifest** -> `install`. Opens the one screen straight away with fresh answers. No preamble, no menu - it just starts.
- **Existing Vortex project** (manifest present) -> opens the one screen pre-filled from the manifest, with the current version shown in the header. Leaving the version as-is and submitting is a **configure**; changing the version via the inline selector and submitting is an **update**. Configure-vs-update is therefore the version control on the one screen, not a separate menu.

Calling an operation explicitly skips detection and opens the one screen already in that mode (e.g. `vortex update` opens with the version selector focused; `--to 1.40` preselects the target).

## The one screen

```
 ╭ Vortex · acme ─────────────────────────────────╮
 │  ⛭ VORTEX                      version 1.38 ▸   │  banner + version (▸ change = update)
 │                                                 │
 │  ❯ General      name · profile · theme          │
 │    Services     Solr · Redis · Clamav           │  the customizer question panels
 │    CI/Hosting   GitHub Actions · Lagoon         │
 │    …                                            │
 │                                                 │
 │  [ Submit ]   [ Cancel ]                        │  actions (later: [ Apply to remote ])
 ╰─────────────────────────────────────────────────╯
       ↑↓ move · enter open · type to edit
```

- **Header** - banner plus the Vortex version. For an existing project the version is the current one and is changeable inline; that inline change is what turns a configure into an update. For a fresh project it defaults to latest and is not prominent.
- **Body** - the customizer question panels (already built).
- **Actions** - `Submit` / `Cancel` today, with room for more buttons on the same screen later.
- **After submit** - a processing pass (fetch template, apply answers, write files) then a completion summary with the file count and next steps. Printed after the screen closes; not a separate interactive screen.

## State model - one operation, three names

| Situation | Name | Inputs |
|---|---|---|
| Empty directory | install | version = latest, fresh answers |
| Existing project, version unchanged | configure | version = current, answers reloaded and tweaked |
| Existing project, version changed | update | version = newly picked, answers reloaded and tweaked |

All three are the same core: `materialize(template@version, answers) -> write files -> review via git`. Writing over the repo only touches template-managed files; the user reviews with `git diff` and commits what they want.

## Persistence - the manifest

A per-project manifest (e.g. `.vortex/manifest.yml`) in the consumer repo stores this project's **answers** plus its **current template version**. It is written on every run and is what lets bare `vortex` detect state and pre-fill the screen.

This is distinct from `config/vortex.yml` inside this package, which holds the tool's own question definitions and never ships to consumers.

## Out of scope

- Managing hosting providers (Lagoon, Acquia) - their own web UIs and CLIs already do this. `doctor` only checks that prerequisites (tokens, variables) are in place.
- The one remote surface `vortex` may own is a non-interactive `repo:setup` (a GitHub labels and branch-protection preset) - a separate command, added later, never a provider-mirroring TUI. Once it exists it could surface as the `Apply to remote` button on the one screen.
