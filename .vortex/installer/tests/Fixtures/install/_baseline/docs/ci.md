# Continuous Integration

In software engineering, continuous integration (CI) is the practice of merging
all developer working copies to a shared mainline several times a day.
Before feature changes can be merged into a shared mainline, a complete build
must run and pass all tests on CI server.

## GitHub Actions

This project uses [GitHub Actions](https://github.com/features/actions) as a
CI server: it imports production backups into fully built codebase and runs
code linting and tests. When tests pass, a deployment process is triggered for
nominated branches (usually, `main` and `develop`).

Refer to https://vortex.drevops.com/latest/usage/ci for more information.

### Skipping CI build

Add `[skip ci]` to the commit subject to skip CI build. Useful for documentation
changes.

### SSH

GitHub Actions does not supports shell access to the build, but there is an
action provided withing the `build` job that allows you to run a build with SSH
support.

Use "Run workflow" button in GitHub Actions UI to start build with SSH support
that will be available for 120 minutes after the build is finished.

