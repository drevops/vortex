# Deployment

For information on how deployment works, see
[Vortex Deployment Documentation](https://www.vortextemplate.com/docs/deployment).

[//]: # (#;< HOSTING_ACQUIA)

## Hosting provider

This project is hosted on [Acquia Cloud](https://www.acquia.com/products/drupal-cloud).

See [Acquia hosting documentation](https://www.vortextemplate.com/docs/hosting/acquia)
for setup and configuration details.

### Deployment workflow

1. Code is pushed to GitHub (source repository).
2. CI builds and tests the code.
3. On success, CI builds an artifact and pushes to Acquia Cloud (destination
   repository).
4. Acquia Cloud runs deployment hooks.

### Branch naming on Acquia Cloud

- Feature branches (`feature/ABC-123`) → same name on Acquia
- Release tags (`0.1.4`) → `deployment/0.1.4` branch on Acquia

### Important rules

- No direct pushes to Acquia Cloud repository.
- Only Technical Lead and Deployer user should have access to Acquia repository.
- Technical Lead should regularly clean up `feature/*` and `bugfix/*` branches.

[//]: # (#;> HOSTING_ACQUIA)

[//]: # (#;< HOSTING_LAGOON)

## Hosting provider

This project is hosted on [Lagoon](https://www.amazee.io/lagoon).

See [Lagoon hosting documentation](https://www.vortextemplate.com/docs/hosting/lagoon)
for setup and configuration details.

### Database refresh

To refresh the database in an existing Lagoon environment with production data:

```bash
VORTEX_DEPLOY_BRANCH=<YOUR/BRANCH-NAME> VORTEX_DEPLOY_ACTION=deploy_override_db ahoy deploy
```

[//]: # (#;> HOSTING_LAGOON)

## Project-specific configuration

<!-- Add project-specific deployment configuration below -->
