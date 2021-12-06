# Releasing

[git-flow](https://danielkummer.github.io/git-flow-cheatsheet/) is used to manage releases.

Note: after completing the release, commits must be manually pushed from `master` to `production` branch.

## Release outcome
1. Release branch exists as `release/X.Y.Z` in GitHub repository.
2. Release tag exists as `X.Y.Z` in GitHub repository.
3. The `HEAD` of the `master` branch has `X.Y.Z` tag.
4. The hash of the `HEAD` of the `master` branch exists in the `develop` branch. This is to ensure that everything pushed to `master` exists in `develop` (in case if `master` had any hot-fixes that not yet have been merged to `develop`).
5. There are no PRs in GitHub related to the release.
6. The hash of the `HEAD` of the `production` branch matches the hash of the `HEAD` of `master` branch.

## Version Number
Release versions are numbered according to [Semantic Versioning](https://semver.org/).
Given a version number X.Y.Z:
* X = Major release version. No leading zeroes.
* Y = Minor Release version. No leading zeroes.
* Z = Hotfix/patch version. No leading zeroes.

Examples:
* Correct: `0.1.0`, `1.0.0` , `1.0.1` , `1.0.10`
* Incorrect: `0.1` , `1` , `1.0` , `1.0.01` , `1.0.010`
