# Vortex Tooling

Helper scripts that ship with [Vortex](https://github.com/drevops/vortex),
the Drupal project template by [DrevOps](https://www.drevops.com). They run
the host-side and in-container operations your project relies on.

Composer distributes this package as `drevops/vortex-tooling`. Add it to
your Drupal consumer project.

## Installation

```bash
composer require drevops/vortex-tooling:~2.0.0
```

Always install with a `~` constraint. It accepts patch releases but holds
the minor version, so a new minor release can't reach your deployments
until you raise the constraint yourself:

```json
"drevops/vortex-tooling": "~2.0.0",
```

After installing, run the shipped scripts as Composer binaries from
`vendor/bin/vortex-<script-name>`.

## Host requirements

The scripts are PHP and use PHP internals wherever possible:

- HTTP requests go through the PHP `curl` extension.
- The `zlib` stream wrapper decompresses gzip archives.
- File discovery, comparison, and removal are native PHP.

The remaining external commands are the tools whose job can't run
in-process:

- `docker` (with the `compose` plugin) - all container operations.
- `git` - repository reset and artifact deployment.
- `ssh-keygen`, `ssh-agent`, `ssh-add` - SSH key loading for deployments
  and database downloads.
- `unzip` - only when fetching a database dump from a `.zip` URL.
- `pygmy`, `ahoy`, `lsof` - local development stack checks in
  `vortex-doctor` (`lsof` is used only for the port check on macOS).
- Hosting provider CLIs (`lagoon`, `acli`) - downloaded automatically when
  not already installed on the host.

Scripts that run inside the CLI container (`vortex-login`,
`vortex-logout`, `vortex-info`, `vortex-provision`, database
import/export) also rely on the project's `vendor/bin/drush` and the
database client in the container image.

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
repository. The commit message body records the source commit SHA.

## Extending provisioning

Logic that must run on every deploy, such as enabling modules, running
migrations, or seeding content, is a `DeployStep` plugin. The
[`deploy_steps`](https://www.drupal.org/project/deploy_steps) module
discovers these plugins. The `provision` script runs `drush deploy:hook`
after the core provisioning steps: database import or profile install,
database updates, configuration import, and cache rebuild. The plugins run
around `deploy:hook`, grouped by phase and ordered by weight.

Add a plugin in any enabled module's `src/Plugin/DeployStep/` namespace.
See the
[provisioning documentation](https://www.vortextemplate.com/docs/drupal/provision)
and the
[deployment documentation](https://www.vortextemplate.com/docs/deployment)
for the full reference.

## Customization

Patch a script only when [extending provisioning](#extending-provisioning)
isn't enough. That's the case when you need to change how a shipped script
*itself* behaves: alter a step it already runs, change a default, or fix
something upstream that no `DeployStep` plugin can reach.

Composer installs the scripts into `vendor/`, and the next
`composer update` overwrites any edit made there. Don't fork the scripts
or edit them in place. Apply a patch with
[`cweagans/composer-patches`](https://github.com/cweagans/composer-patches)
instead, declared in your project's `composer.json`. Composer re-applies
the patch whenever it installs or updates the package, so your
customization survives dependency updates and stays in version control
with the rest of your project.

## Testing

The scripts are tested at 2 levels:

- **Unit tests** - [PHPUnit](https://phpunit.de/) tests provide full unit
  coverage of the scripts, with external commands mocked. They live in
  [`tests/`](https://github.com/drevops/vortex/tree/main/.vortex/tooling/tests)
  in the source repository.
- **Integration tests** - the
  [functional tests](https://github.com/drevops/vortex/tree/main/.vortex/tests)
  of the parent project, [Vortex](https://github.com/drevops/vortex),
  provision a real project and run the scripts to check that they work
  together.

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
