# Architecture

## File structure

The repository file structure follows the structure defined in
[drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project)
.

| Directory                             | Type      | Purpose                                                                                                                                                                             |
|---------------------------------------|-----------|-------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `.circleci`                           | Directory | CircleCI configuration files.                                                                                                                                                       |
| `.data`                               | Directory | Downloaded database dump files. Excluded from the repository.                                                                                                                       |
| `.github`                             | Directory | GitHub configuration files.                                                                                                                                                         |
| `.docker`                             | Directory | Docker configuration files.                                                                                                                                                         |
| `docs`                                | Directory | Your project-specific documentation.                                                                                                                                                |
| `drush`                               | Directory | Drush configuration files.                                                                                                                                                          |
| `hooks`                               | Directory | Acquia hooks. Removed if Acquia hosting is not in use.                                                                                                                              |
| `patches`                             | Directory | patches for Drupal core and contrib modules.                                                                                                                                        |
| `scripts`                             | Directory | Composer, DrevOps and custom project-specific scripts.                                                                                                                              |
| `tests`                               | Directory | Scripts and non-Drupal tests.                                                                                                                                                       |
| `web`                                 | Directory | Drupal root directory.                                                                                                                                                              |
| `.ahoy.yml`                           | File      | Ahoy configuration file.                                                                                                                                                            |
| `.ahoy.local.example.yml`             | File      | An example of local Ahoy configuration file.                                                                                                                                        |
| `.dockerignore`                       | File      | [Docker configuration file](https://docs.docker.com/engine/reference/builder/#dockerignore-file) to control the inclusion or exclusion of the files passed to Docker for the build. |
| `.editorconfig`                       | File      | [EditorConfig](https://editorconfig.org/) helps maintain consistent coding styles for multiple developers working on the same project across various editors and IDEs.              |
| `.env`                                | File      | Environment variables list file. Main place to control project workflow using DrevOps variables.                                                                                    |
| `.env.local.example`                  | File      | Example of the local environment file used to override environment variables to alter the workflow when developing locally.                                                         |
| `.gitignore`                          | File      | Specifies intentionally untracked files to ignore.                                                                                                                                  |
| `.gitignore.deployment`               | File      | Specifies intentionally untracked files to ignore when deploying an artifact.                                                                                                       |
| `.lagoon.yml`                         | File      | Lagoon configuration file. Removed if Lagoon hosting is not in use.                                                                                                                 |
| `behat.yml`                           | File      | Behat [configuration](https://docs.behat.org/en/latest/user_guide/configuration.html) file.                                                                                         |
| `composer.json`                       | File      | Composer [configuration](https://getcomposer.org/doc/04-schema.md) file.                                                                                                            |
| `docker-compose.yml`                  | File      | Configuration file for [Docker Compose](https://docs.docker.com/compose/).                                                                                                          |
| `docker-compose.override.example.yml` | File      | Example override file for Docker Compose configuration.                                                                                                                             |
| `phpcs.xml`                           | File      | PHP CodeSniffer [configuration](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Advanced-Usage#using-a-default-configuration-file) file.                                          |
| `phpstan.neon`                        | File      | PHPStan [configuration](https://phpstan.org/config-reference) file.                                                                                                                 |
| `README.md`                           | File      | Project main readme file.                                                                                                                                                           |
| `renovate.json`                       | File      | Renovate [configuration](https://docs.renovatebot.com/self-hosted-configuration/) file.                                                                                                                                                        |

## Scripts

DrevOps offers a set of scripts designed to orchestrate workflows. These scripts
are written in Bash and adhere to
the [POSIX standard](https://en.wikipedia.org/wiki/POSIX).

Using scripts instead of compiled binaries allows for **in-place per-project
overrides** without needing to learn an additional programming language, compile
sources, or rely on upstream dependencies. This means that you can modify any
DrevOps script to suit your needs within your project repository instead of
waiting
for upstream to make changes to support a feature required in your project.

The scripts are also self-contained, making it possible to copy them
individually
to projects that do not utilize DrevOps.

### Centralized Workflow Approach

The scripts aim to centralize workflows instead of adjusting them for every
environment (local, CI, dev, prod, etc.), reducing multiple points of failure.
This means that a developer updating a local workflow won't forget to update it
in CI, and so on. Environment variables control the flow, with the same
operations and order, but certain operations can be enabled or disabled
depending on the environment.

In practice, this means that a script for downloading a database is called from
Ahoy for local development, from CI configuration, and from the hosting
configuration file is the same script `./scripts/drevops/download-db`.

Furthermore, the called script is a _router_ script that invokes other, more
specific scripts (by sourcing them) based on the project configuration. This
design keeps the entry point consistent while allowing implementation updates
as needed without modifying the entry point everywhere.

For example, changing the database download source from `lagoon` to `s3` would
not require changes to any local, CI, or hosting scripts. And it would not
require
a developer to learn how to use `s3` to download a database or even know how
that download process is setup.

If a new database download method is introduced, the router script
`./scripts/drevops/download-db` can be easily extended to accommodate it,
without altering configuration files for services.
