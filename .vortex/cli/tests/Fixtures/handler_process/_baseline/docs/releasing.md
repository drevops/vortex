# Releasing

To learn how releasing works, see the [Vortex releasing
documentation](https://www.vortextemplate.com/docs/releasing).

## Release outcome

After a successful release:

1. The release branch `release/X.Y.Z` exists in the GitHub repository.
2. The release tag `X.Y.Z` exists in the GitHub repository.
3. The `HEAD` of the `main` branch has the `X.Y.Z` tag.
4. The hash of the `HEAD` of the `main` branch exists in the `develop`
   branch.
5. GitHub has no PRs related to the release.
6. The hash of the `HEAD` of the `production` branch matches the hash of
   the `HEAD` of the `main` branch.

## Version scheme

This project uses [Calendar Versioning](https://calver.org/) (`YY.M.Z`):

- `YY` = Short year
- `M` = Short month
- `Z` = Hotfix/patch version

Examples: `__VERSION__`, `__VERSION__`, `__VERSION__`, `__VERSION__`

## Project-specific configuration

<!-- Add project-specific releasing configuration below -->
