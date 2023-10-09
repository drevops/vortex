# Authoring tests

DrevOps uses [Bats](https://github.com/bats-core/bats-core) for testing.
Bats is a TAP-compliant testing framework for Bash. It provides a simple way to
verify that the UNIX programs you write behave as expected.

See [Bats documentation](https://bats-core.readthedocs.io/) for more information.

## Installation

```bash
npm install --prefix .drevops/tests
```

## Usage

```bash
# Run a single test.
bats .drevops/tests/bats/helpers.bats

# Some tests require Composer and Docker Hub tokens.
TEST_GITHUB_TOKEN=<yourtoken> TEST_DOCKER_USER=<youruser> TEST_DOCKER_PASS=<yourpass> bats .drevops/tests/bats/workflow.smoke.bats

# To preserve test run directory.
bats --no-tempdir-cleanup .drevops/tests/bats/helpers.bats

# To override Bats temporary directory where tests are ran (required for Docker tests).
mkdir -p $HOME/.bats-tmp # run once
TMPDIR=$HOME/.bats-tmp bats .drevops/tests/bats/helpers.bats

# Run all tests, preserving the temporary directory.
TEST_GITHUB_TOKEN=<yourtoken> \
TEST_DOCKER_USER=<youruser> \
TEST_DOCKER_PASS=<yourpass> \
TMPDIR=$HOME/.bats-tmp \
bats --no-tempdir-cleanup .drevops/tests/bats/*.bats
```

## Updating test assets

Some tests use test fixtures such as Drupal database snapshots.

### Updating demo database file dump

1. Run fresh build of DrevOps locally:
```bash
echo "DREVOPS_DRUPAL_PROFILE=standard">>.env.local
echo "DREVOPS_PROVISION_USE_PROFILE=1">>.env.local
rm .data/db.sql
AHOY_CONFIRM_RESPONSE=1 ahoy build
```
2. Check that everything looks correctly on the site.
3. Export DB
```bash
ahoy export-db
```
4. Make sure that exported DB does not have data in `cache_*` and `watchdog` tables.
5. Upload DB to https://github.com/drevops/drevops/wiki as a test file (`db.distN.sql`).
6. Update references in code from `db.demo.sql` to `db.distN.sql`.
7. Run CI build.
8. Revert updated references to `db.demo.sql`.
9. Update `db.demo.sql` in Wiki.
10. Merge branch to `main`.
11. Wait for CI to pass.
12. Remove `db.distN.sql` from Wiki.

### Updating demo database Docker image

!!! note "Work in progress"

    The documentation section is still a work in progress.
