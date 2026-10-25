# Documentation System Guide

## Overview

Docusaurus-based documentation website published to
https://www.vortextemplate.com

**Technology**: Docusaurus, React, MDX, Jest, cspell

## Commands

```bash
cd .vortex/docs

yarn install      # Install dependencies
yarn start        # Development server
yarn build        # Production build
yarn test         # Run Jest tests
yarn spellcheck   # American English validation
yarn lint         # Code quality checks
yarn lint-fix     # Auto-fix issues
yarn lint-prose   # Prose style checks (Vale)
```

## Key Directories

```text
docs/
├── content/           # MDX documentation pages
├── src/components/    # React components
├── tests/unit/        # Jest tests
├── sidebars.js        # Sidebar configuration
└── docusaurus.config.js
```

## Writing Guidelines

- **American English** spelling throughout
- **Sentence case** for headings (capitalize only the first letter and
  proper nouns)
- Proper nouns: Vortex, GitHub, Drupal, Docker Compose, CircleCI
- **`Vortex` must always be bold** (`**Vortex**`) when it appears in prose.
  Applies to every `.mdx` file under `content/`. Exception: headings, link
  labels, code fences, and inline code (`` `Vortex` ``) where Markdown
  emphasis is not appropriate.
- Acronyms: CI/CD, SSH, API, BDD, PHPUnit
- Contractions (`doesn't`, `it's`, `can't`), present tense, and `you` for
  the reader.
- Digits for numbers from 2 to 10 (`2 majors`), the Oxford comma, and
  `for example` instead of `e.g.`.
- Environment variables, file names, commands, and package names are inline
  code, in link labels too. Inside single-line JSX or HTML elements such as
  `<summary>`, use `<code>` tags instead of backticks.
- No `easy`, `simply`, `just`, `clearly`, marketing adjectives, or em and en
  dashes (use a spaced hyphen).
- Wrap lines at 76 characters; markdownlint (`MD013`) enforces it. Code
  blocks, tables, and headings are exempt, but code inside JSX such as a
  `<TabItem>` is not, so split a long command there with `\`.

## Features page

`content/features.mdx` lists what the template ships, 1 tab per area.

- Link every product the template ships or integrates with to its homepage
  or repository: tools, services, hosting platforms, Drupal modules, and
  DrevOps packages such as `drevops/vortex-tooling` or Behat Steps. Link a
  product once per tab, on its first mention. Languages and platforms named
  only as context, such as PHP or Drupal in passing, stay unlinked.
- End each tab with a `➡️ See` line linking the docs section that covers
  the area.
- Update the page in the same PR as a change that adds, removes, or
  replaces a shipped tool, service, module, or integration.
- Keep "planned" notes in sync with `content/contributing/roadmap.mdx`.

## Prose linting

`yarn lint-prose` runs [Vale](https://vale.sh) over `content/**/*.mdx`.
Errors fail the run and CI; warnings are advisory. Lint a single page with
`yarn lint-prose --glob='content/faqs.mdx'`.

- `.vale.ini` selects the rules. The `Google` and `write-good` packages are
  pinned there by release URL and downloaded into the git-ignored part of
  `.vale/` by `vale sync`, which runs on `yarn install`.
- `.vale/Vortex/` is the committed house style. A rule that adjusts an
  upstream rule inherits it with `extends: Google.<Rule>` and edits its
  lists with `exceptions+`, `tokens-` or `swap-`; the upstream rule is then
  switched off in `.vale.ini`.
- `.vale/config/vocabularies/Vortex/accept.txt` holds product names with
  their exact casing. Add a new product name there; it is then enforced in
  prose and accepted in headings.
- A heading that fails sentence case because of a proper noun gets an entry
  in `.vale/Vortex/Headings.yml`.
- Fix the prose or fix the rule. Do not silence an alert with Vale control
  comments.
- Blockquotes are skipped because they quote upstream projects verbatim. The
  generated table in `content/development/variables.mdx` is skipped too; the
  hand-written prose that introduces it is linted.

## Diagrams

**Use ASCII diagrams, not Mermaid.** ASCII renders the same in every viewer
(GitHub preview, terminal, copied into chat, plain-text exports), needs no
plugin, and stays readable in source. Prefer **vertical** ASCII flows
(top-to-bottom with `│` connectors and `▼` arrows) over horizontal ones,
because they read better on narrow page widths.

Reserve Mermaid for the rare case where the diagram needs interactive
behavior the static renderer can't provide. Don't use it for ordinary
flowcharts.

## Sidebar Configuration

`sidebars.js` defines the categories. For subdirectories:

- The category label comes from an explicit definition or the directory
  name.
- Use `sidebar_label: Overview` in README.mdx for the first item.

## Documentation videos

9 terminal demo videos live in `static/img/` (`cli-install.*`, `build.*`,
`provision.*`, `lint.*`, `test.*`, `test-bdd.*`, `info.*`, `doctor.*`,
`doctor-info.*`). Each is a `.json` asciicast plus an `.svg` and a `.png`
poster. A single PHP script (`.utils/update-videos.php`) driven by the
shared `VideoRecorder` class generates them. Run
`ahoy update-videos [names]` from `.vortex/`. See `.vortex/CLAUDE.md` for
the staleness triggers (which code change invalidates which video).

| Command                                | Regenerates                                    |
|----------------------------------------|------------------------------------------------|
| `ahoy update-videos`                   | Wipe workspace + bootstrap + record all        |
| `ahoy update-videos lint provision`    | Wipe + bootstrap + record only lint, provision |
| `ahoy update-videos lint,test`         | Comma-separated list also accepted             |
| `ahoy update-videos --keep lint`       | Skip bootstrap, re-record lint only            |
| `ahoy update-videos --keep lint test`  | Skip bootstrap, re-record lint and test        |

**Workspace**: the orchestrator uses a single fixed workspace at
`.artifacts/tmp/videos-workspace/` (gitignored, Docker Compose project name
`vortex_videos`). The default invocation tears it down with `ahoy reset`
and `rm`, then bootstraps from scratch. `--keep` reuses the existing
workspace and exits with an error if the workspace is missing or the
Docker stack isn't running.

**Config**: per-video settings (command, speed, terminal cols/rows, poster
timestamp, typer on/off) live in the `VIDEOS` array at the top of
`update-videos.php`.

**Pipeline**:

1. Runs the CLI non-interactively using `--uri=<project_root>`, producing
   `$workspace/star_wars`. It runs through `expect` instead when
   `cli-install` is in the requested set.
2. If any of `build`, `provision`, `lint`, `test`, `test-bdd`, `info`,
   `doctor`, `doctor-info` is requested, `ahoy build` runs **once** in
   `$workspace/star_wars`, either as the recorded `build` video or
   silently.
3. The remaining requested commands (`info`, `doctor`, `doctor-info`,
   `provision`, `lint`, `test`, `test-bdd`) are recorded in that same
   `star_wars` directory, in fixed order.
4. Each cast is decoded to plain text at `.artifacts/videos/<name>.txt` and
   checked before it's rendered. A recording whose output reports an
   error, a warning, or a failure isn't turned into an SVG.
5. The workspace and Docker stack stay in place at exit, so the next
   `--keep` invocation can reuse them. The next non-`--keep` run tears down
   a stale workspace at its **start**.

**Output check**: a published demo claims that the command it records runs
clean, so the decoded transcript is matched against
`VideoRecorder::ISSUE_PATTERNS`. `VideoRecorder::ISSUE_EXEMPT_PATTERNS`
removes the fragments that carry a marker word without reporting anything:
paths, URLs, package names, command line options, and zero counts such as
`0 errors` or `warnings: 0`.

The report names every offending line with its line number in the
transcript, and groups repeated identical lines. There is no override
flag, so output that can't be fixed here is fixed at the command that
emits it. The transcript is working material, not a published artifact:
`.artifacts/` is gitignored and nothing under it is committed.

**Iterating on one video** - use `--keep` so the install and build happen
only once, then replay the recording against the preserved project:

```bash
cd .vortex
ahoy update-videos --keep lint     # full bootstrap + record lint, keep workspace
# tweak the lint command or the recorder
ahoy update-videos --keep lint     # reuse the kept workspace, record lint only
ahoy update-videos lint            # fresh bootstrap, no --keep, cleans up at end
# (or manually) rm -rf .artifacts/tmp/videos-workspace
```

`update-videos` doesn't auto-commit. Review the diff under `static/img/`
and commit manually. The `prepare-vortex-release` skill regenerates every
video as part of the release checklist.

## Troubleshooting

```bash
# Build failures
yarn build --verbose

# Spellcheck failures
yarn spellcheck
npx cspell "content/**/*.md"

# Test failures
yarn test --verbose
yarn test --updateSnapshot
```
