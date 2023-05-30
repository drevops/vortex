# 🚚 Deploy

The deployment process `deploy.sh` router script designed to deploy code to a
remote location by calling the relevant scripts based on the type of deployment
defined in `DREVOPS_DEPLOY_TYPE` variable.

After setting up the deployment integration, you can begin using it by adding
the `DREVOPS_DEPLOY_PROCEED` variable with a value of `1` in the CircleCI user
interface. This variable is used as a failsafe to prevent accidental
deployments.

## Deployment action

By default, an existing database will be retained during a deployment. To change
this behavior and overwrite the database, set the `DREVOPS_DEPLOY_ACTION`
variable to `deploy_override_db`.

## Skipping deployments for specific Pull Requests or branches

To skip a specific Pull Request or branch using the `DREVOPS_DEPLOY_SKIP`
variable, you would define additional environment variables with a specific
naming convention in your CI configuration.

Here's an example of how this can be done:

### Skipping a specific Pull Request:

   Suppose you want to skip the deployment for Pull Request number 42. You would
   set the following environment variable:

   ```bash
   DREVOPS_DEPLOY_SKIP_PR_42=1
   ```

### Skipping a specific branch:

   Suppose you want to skip the deployment for a branch named `feature-x`. You
   would first create a "safe" version of the branch name by replacing any
   special characters with underscores.

   Set the following environment variable:

   ```bash
   DREVOPS_DEPLOY_SKIP_BRANCH_FEATURE_X=1
   ```
