# Vortex Tooling

Helper scripts that ship with [Vortex](https://github.com/drevops/vortex), the
Drupal project template by [DrevOps](https://www.drevops.com). They implement
the host-side and in-container operations your project relies on.

This package is distributed via Composer as `drevops/vortex-tooling` and needs
to be added to your Drupal consumer project site.

## Installation

```bash
composer require drevops/vortex-tooling
```

Once installed, you run the shipped scripts from
`vendor/drevops/vortex-tooling/src/<script-name>`.

## Read-only mirror

> [!IMPORTANT]
> This repository is a **read-only mirror** of the
> [`.vortex/tooling/`](https://github.com/drevops/vortex/tree/main/.vortex/tooling)
> directory in the [`drevops/vortex`](https://github.com/drevops/vortex)
> monorepo. **Do not open issues or pull requests here.** All development
> happens in the parent repository.

| You want to                          | Go to                                                                       |
|--------------------------------------|-----------------------------------------------------------------------------|
| Report a bug                         | [drevops/vortex/issues](https://github.com/drevops/vortex/issues)           |
| Propose a change                     | [drevops/vortex/pulls](https://github.com/drevops/vortex/pulls)             |
| Browse the source of truth           | [drevops/vortex/.vortex/tooling](https://github.com/drevops/vortex/tree/main/.vortex/tooling) |
| Read the documentation               | [www.vortextemplate.com](https://www.vortextemplate.com)                    |

Each commit in this repository corresponds to a commit in the parent
repository. The commit message body records the source commit SHA for
provenance.

## Extending provisioning

The `provision` script runs your own scripts after the core provisioning steps
complete (database import or profile install, database updates, configuration
import, cache rebuild, deployment hooks, and sanitisation). This is the
recommended way to add project-specific provisioning logic - enabling modules,
running migrations, seeding content, and so on - without touching the shipped
scripts.

Drop an executable script into your project's `scripts/` directory, named with
the `provision-` prefix and the `.sh` extension. The `provision` script
discovers and runs every matching file in filename order; use a two-digit
number to sequence them (`provision-10-...`, `provision-20-...`).

The layout looks like this:

```text
your-project/
├── .ahoy.yml
├── composer.json
├── scripts/
│   ├── provision-10-example.sh   # shipped example - copy or remove
│   ├── provision-20-migration.sh # shipped example - copy or remove
│   └── provision-30-custom.sh    # your own hook script
├── vendor/
│   └── drevops/vortex-tooling/src/provision   # runs each provision-*.sh in order
├── web/                          # Drupal web root
│   └── ...
└── ...
```

The template ships runnable examples you can copy or remove -
[`scripts/provision-10-example.sh`](https://github.com/drevops/vortex/blob/main/scripts/provision-10-example.sh)
and
[`scripts/provision-20-migration.sh`](https://github.com/drevops/vortex/blob/main/scripts/provision-20-migration.sh).
See the
[provisioning documentation](https://www.vortextemplate.com/docs/drupal/provision#running-custom-scripts)
for the full reference.

## Customisation

Reach for this only when [extending provisioning](#extending-provisioning) is
not enough - when you need to change how a shipped script *itself* behaves
(alter a step it already runs, change a default, or fix something upstream that
no post-provision hook can reach). It is the last resort.

The scripts are installed read-only into `vendor/`, so do not fork them or edit
them in place: those changes are lost on the next `composer update`. Instead,
apply a patch managed by
[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches) and
declared in your project's `composer.json`. The patch is re-applied
automatically whenever the package is installed or updated in your project, so
your customisation survives dependency updates and stays version-controlled
alongside the rest of your project.

## Testing

The scripts are covered at two levels:

- **Unit tests** - [BATS](https://github.com/bats-core/bats-core) tests provide
  full unit coverage of the scripts, with external commands mocked. They live
  in
  [`tests/`](https://github.com/drevops/vortex/tree/main/.vortex/tooling/tests)
  in the source repository.
- **Integration tests** - end-to-end coverage comes from the parent project
  that uses these scripts,
  [Vortex](https://github.com/drevops/vortex). Its
  [functional tests](https://github.com/drevops/vortex/tree/main/.vortex/tests)
  provision a real project and run the scripts to verify they work together.

## License

[GPL-3.0-or-later](https://www.gnu.org/licenses/gpl-3.0.html)
