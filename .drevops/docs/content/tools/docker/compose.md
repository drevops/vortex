# 🐳 Docker Compose

!!! note "Work in progress"

    The documentation section is still a work in progress.

## Using `docker-compose.yml`

Note that `docker-compose.yml` does not support default values evaluation in
variables assignment. E.g.,

    environment:
      VARIABLE2: ${VARIABLE1:-$VARIABLE3}

`$VARIABLE3` will not be evaluated if `$VARIABLE1`
is not provided (it will be a literal string `$VARIABLE3`).

### Validate `docker-compose.yml`

    docker compose -f docker-compose.yml config

### Host volume mounting in Docker-based projects

To share application code between services (containers), Docker uses volumes.
When used in non-development environments, containers have access to
the same shared files using volumes and these volumes do not need to be
mounted from the host.

But for development environment, when the code constantly changes on the host,
we need to have these changes synchronized into all containers. Since we are
using single `docker-compose.yml` file for all environments, we have to
accommodate both cases, so we are specifying an override for the same directory
as a mounted volume as a commented-out lines, which will be automatically
uncommented in CI.

See [Docker Compose reference](https://docs.docker.com/compose/compose-file/compose-file-v2/#volume-configuration-reference) about volumes.
