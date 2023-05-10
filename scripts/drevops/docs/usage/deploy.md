# Deploy

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

1. Skipping a specific Pull Request:

   Suppose you want to skip the deployment for Pull Request number 42. You would
   set the following environment variable:

   ```
   DREVOPS_DEPLOY_SKIP_PR_42=1
   ```

   In your script, you would check for this variable and skip deployment
   accordingly:

   ```bash
   PR_NUMBER="42" # This should be dynamically fetched from your CI/CD environment.

   if [ "$DREVOPS_DEPLOY_SKIP_PR_${PR_NUMBER}" = "1" ]; then
       echo "Skipping deployment for Pull Request ${PR_NUMBER}"
       exit 0
   fi
   ```

2. Skipping a specific branch:

   Suppose you want to skip the deployment for a branch named `feature-x`. You
   would first create a "safe" version of the branch name by replacing any
   special characters with underscores. In this case, it's already safe.

   Set the following environment variable:

   ```
   DREVOPS_DEPLOY_SKIP_BRANCH_FEATURE_X=1
   ```

   In your script, you would check for this variable and skip deployment
   accordingly:

   ```bash
   BRANCH_NAME="feature-x" # This should be dynamically fetched from your CI/CD environment.
   SAFE_BRANCH_NAME=$(echo "$BRANCH_NAME" | tr -c '[:alnum:]' '_')

   if [ "$DREVOPS_DEPLOY_SKIP_BRANCH_${SAFE_BRANCH_NAME}" = "1" ]; then
       echo "Skipping deployment for branch ${BRANCH_NAME}"
       exit 0
   fi
   ```

With these examples, you can selectively skip deployments for specific Pull
Requests or branches based on the environment variables you set.
