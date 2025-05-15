# Deployment process

Refer to https://vortex.drevops.com/workflows/deployment for more information.

## Workflow

1. Code is authored on a local machine by a developer.
2. Once the code is ready, it is pushed to GitHub. A pull request needs to be
   opened for this code change.
3. The CI "listens" for code changes and will start an automated build.
4. At the end of the build, when all tests have passed, the CI will trigger a
   deployment to Lagoon.
5. Once deployed, a PR environment will appear with a PR name. The database will
   be taken from production environment.
   All pending update hooks and other deployment operations will run during
   deployment.

Once PR is closed, the environment will be automatically removed.

[//]: # (#;< HOSTING_ACQUIA)

GitHub is a primary code repository for this project (aka "source repository").
Acquia Cloud is a hosting provider for this project and it also has a git
repository (aka "destination repository").

The website gets deployed using artifact built on CI and pushed to Acquia Cloud.

There are 2 types of deployments: feature branches and release tags. They are
exactly the same except for the resulting branch name on Acquia Cloud (see
below).

## Setup

1. Create a Deployer user (deployer@yourcompany.com) account in Acquia.
2. Add this user to Acquia Cloud application with a role that allows to push
   code and use Cloud API.
3. Login with Deployer user and go to Acquia Cloud UI->Account->Credentials->
   Copy email and key from section "Cloud API".
4. SSH into non-production server and run `drush ac-api-login`. Enter copied
   email and key when prompted. This will store credentials
   to `$HOME/.acquia/cloudapi.conf`and they will not need to be entered again.
   This allows to use Cloud API drush commands within hooks.
5. Create SSH key (use `deployer+yourproject@yourcompany.com` as an email to
   distinguish SSH keys) and add it to this user. This key cannot be re-used
   between projects!
6. Login to CircleCI, go to Settings->SSH Permissions->Add SSH Key and paste *
   private* key. This allows to push the code from CI to Acquia git repository.
7. Copy SHH key fingerprint (looks
   like `16:02:e3:ca:33:04:82:58:e8:e9:3e:5d:82:17:86:b1`) and replace it
   inside `.circleci/config.yml`.

## Deployment workflow

1. Developer updates DB in the Acquia Cloud environment by copying PROD database
   to required environment.
2. Developer pushes code update to the GitHub branch.
3. CI system picks-up the update and does the following:
  1. Builds a website using production DB.
  2. Runs code standard checks and Behat tests on the built website.
  3. Creates a deployment artifact (project files to be pushed to Acquia Cloud
     repository).
  4. Pushes created artifact to the Acquia Cloud repository:
    - for feature-based branches (i.e. `feature/ABC-123` or `bugfix/ABC-123`)
      the code is pushed to the branch with exactly the same name.
    - for release deployments, which are tag-based (i.e. `0.1.4`), the code is
      pushed to the branch `deployment/[tag]` (i.e. `deployment/0.1.4`).
4. Acquia Cloud picks up recent push to the repository and
   runs [post code update hooks](hooks/dev/post-code-update) on the environments
   where code is already deployed.
   OR
4. If the branch has not been deployed into any Acquia Cloud environment yet and
   the developer starts the deployment, Acquia Cloud
   runs [post code deploy hooks](hooks/dev/post-code-deploy) on the environment
   where code is being deployed.

### Release outcome

1. Release branch exists as `deployment/X.Y.Z` in remote GitHub repository.
2. Release tag exists as `X.Y.Z` in remote GitHub repository.
3. The `HEAD` of the `main` branch has `X.Y.Z` tag assigned.
4. The hash of the `HEAD` of the `main` branch exists in the `develop` branch.
   This is to ensure that everything pushed to `main` exists in `developed` (in
   case if `main` had any hot-fixes that not yet have been merged to `develop`).
5. There are no PRs in GitHub related to releases.
6. Release branch is available on Acquia Cloud as `deployment/X.Y.Z` branch.
   Note: we are building release branches on Acquia Cloud out of tags in GitHub.
7. Release branch `deployment/X.Y.Z` is deployed into PROD environment. Note: we
   are NOT deploying tags to Acquia Cloud PROD.

### Important

Since Acquia Cloud becomes a destination repository, the following rules MUST be
followed by all developers:

1. There should be no direct access to Acquia Cloud repository for anyone except
   for project Technical Lead and Deployer user.
2. There should be no pushes to Acquia Cloud repository.
3. There may be `main` or `develop` branch in Acquia Cloud repository.
4. Technical Lead is expected to regularly cleanup `feature/*` and `bugfix/*`
   branches in Acquia Cloud repository.

[//]: # (#;> HOSTING_ACQUIA)

[//]: # (#;< HOSTING_LAGOON)

## Database refresh in Lagoon environments

To fresh the database in the existing Lagoon environment with the database from
production environment, run:

```bash
VORTEX_DEPLOY_BRANCH=<YOUR/BRANCH-NAME> VORTEX_DEPLOY_ACTION=deploy_override_db ahoy deploy
```

## Skipping deployments

You can completely skip deployments by setting the `VORTEX_DEPLOY_SKIP` environment variable to `1`. This can be useful in CI/CD pipelines where you want to run the build and tests but skip the deployment step.

```bash
VORTEX_DEPLOY_SKIP=1 ahoy deploy
```

In CI, you can set the repository variable `VORTEX_DEPLOY_SKIP` to `1` to skip all deployments temporarily.

[//]: # (#;> HOSTING_LAGOON)
