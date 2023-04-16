# Architecture

!!! note "Work in progress"

    The documentation section is still a work in progress.

## Scripts

DrevOps offers a set of scripts designed to orchestrate workflows. These scripts
are written in Bash and adhere to the [POSIX standard](https://en.wikipedia.org/wiki/POSIX).

Using scripts instead of compiled binaries allows for **in-place per-project
overrides** without needing to learn an additional programming language, compile
sources, or rely on upstream dependencies.

The scripts are also self-contained, making it possible to copy them individually
to projects that do not utilize DrevOps.

### Centralized Workflow Approach

The scripts aim to centralize workflows instead of adjusting them for every
environment (local, CI, dev, prod, etc.), reducing multiple points of failure.
This means that a developer updating a local workflow won't forget to update it
in CI, and so on. Environment variables control the flow, with the same
operations and order, but certain operations can be enabled or disabled
depending on the environment.

In practice, this means a script for downloading a database is called from Ahoy
for local development, from CI configuration, and from the hosting configuration
file is the same script `./scripts/drevops/download-db`.

Furthermore, the called script is a router script that invokes other, more
specific scripts (by sourcing them) based on the project configuration. This
design keeps the entry point consistent while allowing implementation updates
as needed without modifying the entry point everywhere.

For example, changing the database download source from `lagoon` to `s3` would
not require changes to any local, CI, or hosting scripts.

If a new database download method is introduced, the router script
`./scripts/drevops/download-db` can be easily extended to accommodate it,
without altering configuration files for services.
