# Continuous Integration

In software engineering, continuous integration (CI) is the practice of merging
all developer working copies to a shared mainline several times a day.
Before feature changes can be merged into a shared mainline, a complete build
must run and pass all tests on CI server.

## Circle CI

This project uses [Circle CI](https://circleci.com/) as a CI server: it imports
production backups into fully built codebase and runs code linting and tests.
When tests pass, a deployment process is triggered for nominated branches
(usually, `main` and `develop`).

Refer to https://vortex.drevops.com/latest/usage/ci for more information.

### Skipping CI build

Add `[skip ci]` to the commit subject to skip CI build. Useful for documentation
changes.

### SSH

Circle CI supports shell access to the build for 120 minutes after the build is
finished when the build is started with SSH support. Use "Rerun job with SSH"
button in Circle CI UI to start build with SSH support.

