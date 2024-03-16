# Git artifact

https://github.com/drevops/git-artifact

> Package and push files to remote repositories

Some hosting providers, like Acquia, restrict certain build operations, making
it necessary to develop and build your site elsewhere before deploying it. This
tool streamlines that process: it uses a `.gitignore.deployment` file to control
which files get transferred, and overwrites the destination repository's history
with each push, while preserving the source history.

DrevOps comes
with [pre-configured `.gitignore.deployment`](../../../../.gitignore.deployment)
file and [deployment script](../../../../scripts/drevops/deploy-artifact.sh)
to build the artifact in CI and push it to the remote repository in Acquia.

## Usage

This tool is used in CI and does not require any manual actions.

## Configuration

The deployment targets can be added to the `.gitignore.deployment` file: it will
replace the standard `.gitignore` file in the artifact repository.

The file already contains all required targets to get the full site build with
production-only dependencies (dev-dependencies are excluded from the code
artifact during the CI build).

Modifying targets in the `.gitignore.deployment` file works just like updating
a regular `.gitignore` file.

It is required to set the following environment variables in CI:

- `DREVOPS_DEPLOY_ARTIFACT_GIT_USER_NAME`: Email address of the user who will be
  committing to a remote repository.
- `DREVOPS_DEPLOY_ARTIFACT_GIT_USER_EMAIL`: Name of the user who will be
  committing to a remote repository.
