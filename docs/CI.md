# Automated builds (Continuous Integration)

In software engineering, continuous integration (CI) is the practice of merging all developer working copies to a shared mainline several times a day.
Before feature changes can be merged into a shared mainline, a complete build must run and pass all tests on CI server.

This project uses [Circle CI](https://circleci.com/) as a CI server: it imports production backups into fully built codebase and runs code linting and tests. When tests pass, a deployment process is triggered for nominated branches (usually, `master` and `develop`).

Add `[skip ci]` to the commit subject to skip CI build. Useful for documentation changes.

## SSH
Circle CI supports shell access to the build for 120 minutes after the build is finished when the build is started with SSH support. Use "Rerun job with SSH" button in Circle CI UI to start build with SSH support.

[//]: # (#;< RENOVATEBOT)

## Automated patching
[renovatebot.com](https://renovatebot.com) integration allows to keep the
project up to date by automatically creating pull requests with updated
dependencies on a daily basis.

### Features
1. 2 schedules:
   1. Minor and patch versions for critical Drupal core and related packages to run daily.
   2. All versions for all other packages to run weekly.
2. A custom label `dependencies` is added on PR creation.
3. Assignees are added on PR creation.
4. Custom branch prefix (`deps/`) for PRs:
  1. `deps/minor-patch-core`
  2. `deps/all-contrib`

[//]: # (#;> RENOVATEBOT)
