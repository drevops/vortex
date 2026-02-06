# Releasing

For information on how releasing works, see
[Vortex Releasing Documentation](https://www.vortextemplate.com/docs/releasing).

## Release outcome

After a successful release:

1. Release branch exists as `release/X.Y.Z` in GitHub repository.
2. Release tag exists as `X.Y.Z` in GitHub repository.
3. The `HEAD` of the `main` branch has `X.Y.Z` tag.
4. The hash of the `HEAD` of the `main` branch exists in the `develop` branch.
5. There are no PRs in GitHub related to the release.
6. The hash of the `HEAD` of the `production` branch matches the hash of
   the `HEAD` of `main` branch.

[//]: # (#;< VERSION_RELEASE_SCHEME_SEMVER)

## Version scheme

This project uses [Semantic Versioning](https://semver.org/) (`X.Y.Z`):

- `X` = Major release version
- `Y` = Minor release version
- `Z` = Hotfix/patch version

Examples: `0.1.0`, `1.0.0`, `1.0.1`, `1.0.10`

[//]: # (#;> VERSION_RELEASE_SCHEME_SEMVER)

[//]: # (#;< VERSION_RELEASE_SCHEME_CALVER)

## Version scheme

This project uses [Calendar Versioning](https://calver.org/) (`YY.M.Z`):

- `YY` = Short year
- `M` = Short month
- `Z` = Hotfix/patch version

Examples: `25.1.0`, `25.11.1`, `25.1.10`, `25.10.1`

[//]: # (#;> VERSION_RELEASE_SCHEME_CALVER)

## Project-specific configuration

<!-- Add project-specific releasing configuration below -->
