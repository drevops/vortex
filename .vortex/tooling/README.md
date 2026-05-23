# Vortex Tooling

Shell scripts that ship with [Vortex](https://github.com/drevops/vortex), the
Drupal project template by [DrevOps](https://www.drevops.com). These scripts
implement the host-side and in-container operations used by Vortex-based
projects: database download/export, deployment, notifications, provisioning,
doctor, project reset, and more.

This package is distributed via Composer as `drevops/vortex-tooling`. Consumer
projects install it as a regular dependency and invoke the shipped scripts at
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

## Installation

```bash
composer require drevops/vortex-tooling
```

After installation, scripts are available at
`vendor/drevops/vortex-tooling/src/`.

## Layout

```text
.
├── src/         # Shipped shell scripts (no extension; executed directly)
├── tests/       # BATS unit tests
└── playground/  # Manual integration scripts (not part of the published package)
```

## Customisation

Customise shipped scripts via
[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches)
declared in your project's `composer.json`. Do not fork or modify scripts
in-place.

## License

[GPL-3.0-or-later](https://www.gnu.org/licenses/gpl-3.0.html)
