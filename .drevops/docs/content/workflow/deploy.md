# 🚚 Deploy

The deployment to a remote location is performed by the
[`scripts/drevops/deploy.sh`](../../../../scripts/drevops/deploy.sh) _router_
script.

The script runs in CI only after all tests pass.

The script deploys the  code to a remote location by calling the
relevant scripts based on the type of deployment defined in `$DREVOPS_DEPLOY_TYPES`
variable as a comma-separated list of one or multiple supported deployment types:
- `webhook` - a webhook URL is called via CURL.
- `artifact` - a code artifact created and sent to a remote repository.
- `lagoon` - a special webhook URL is called via CURL to trigger a deployment in
  Lagoon.
- `docker`- a Docker image is built and pushed to a remote registry.

## Setting up deployments

After setting up the deployment integration, you can begin using it by adding
the `$DREVOPS_DEPLOY_PROCEED` variable with a value of `1` in the CircleCI user
interface. This variable is used as a failsafe to prevent accidental
deployments.

## Using deployments

### Deployment action

By default, an existing database will be retained during a deployment. To change
this behavior and overwrite the database, set the `$DREVOPS_DEPLOY_ACTION`
variable to `deploy_override_db`.

### Skipping deployments for specific Pull Requests or branches

To skip a specific Pull Request or branch using the `$DREVOPS_DEPLOY_SKIP`
variable, you would define additional environment variables with a specific
naming convention in your CI configuration.

Here's an example of how this can be done:

#### Skipping a specific Pull Request:

   Suppose you want to skip the deployment for Pull Request number 42. You would
   set the following environment variable:

   ```bash
   DREVOPS_DEPLOY_SKIP_PR_42=1
   ```

#### Skipping a specific branch:

   Suppose you want to skip the deployment for a branch named `feature-x`. You
   would first create a "safe" version of the branch name by replacing any
   special characters with underscores.

   Set the following environment variable:

   ```bash
   DREVOPS_DEPLOY_SKIP_BRANCH_FEATURE_X=1
   ```
