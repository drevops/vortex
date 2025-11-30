# Releasing

[git-flow](https://danielkummer.github.io/git-flow-cheatsheet/) is used to
manage releases.

Note: after completing the release, commits must be manually pushed
from `main` to `production` branch.

Refer to https://www.vortextemplate.com/docs/workflows/releasing for more information.

## Release outcome

1. Release branch exists as `release/X.Y.Z` in GitHub repository.
2. Release tag exists as `X.Y.Z` in GitHub repository.
3. The `HEAD` of the `main` branch has `X.Y.Z` tag.
4. The hash of the `HEAD` of the `main` branch exists in the `develop` branch.
   This is to ensure that everything pushed to `main` exists in `develop` (in
   case if `main` had any hot-fixes that not yet have been merged
   to `develop`).
5. There are no PRs in GitHub related to the release.
6. The hash of the `HEAD` of the `production` branch matches the hash of
   the `HEAD` of `main` branch.

## Version Number - Calendar Versioning (CalVer)

Release versions are numbered according to [CalVer Versioning](https://calver.org/).

Given a version number `YY.M.Z`:

- `YY` = Short year. No leading zeroes.
- `M` = Short month. No leading zeroes.
- `Z` = Hotfix/patch version. No leading zeroes.

Examples:

- Correct: `25.1.0`, `25.11.1` , `25.1.10`, `25.10.1`, `9.12.0`
- Incorrect: `25.0.0`, `2025.1.1` , `25` , `25.1.00` , `25.01.0`, `25.0.0`, `01.1.0`
