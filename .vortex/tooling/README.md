# Vortex Tooling

Helper scripts that ship with [Vortex](https://github.com/drevops/vortex), the
Drupal project template by [DrevOps](https://www.drevops.com). They implement
the host-side and in-container operations that a consumer project built from
Vortex relies on.

This package is distributed via Composer as `drevops/vortex-tooling`.

## Installation

```bash
composer require drevops/vortex-tooling
```

A consumer project installs this package as a regular dependency and runs the
shipped scripts from `vendor/drevops/vortex-tooling/src/<script-name>`.

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

## Customisation

If you need to change how a shipped script behaves - for example, to add an
extra step, change a default value, or integrate with a service specific to
your project - do not fork the scripts or edit them in place: they are
installed read-only into `vendor/`, so those changes are lost on the next
`composer update`.

Instead, customise a script with a patch managed by
[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches) and
declared in your project's `composer.json`. The patch is re-applied
automatically whenever the package is installed or updated in the consumer
project, so your customisation survives dependency updates and stays
version-controlled alongside the rest of your project.

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
