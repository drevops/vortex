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

## Database refresh in Lagoon environments

To fresh the database in the existing Lagoon environment with the database from
production environment, run:

```
VORTEX_DEPLOY_BRANCH=<YOUR/BRANCH-NAME> VORTEX_DEPLOY_ACTION=deploy_override_db ahoy deploy
```

