# 🔄 Automated updates

DrevOps uses [Renovate](https://renovatebot.com) for automated dependency updates.

The configuration is stored in [`renovate.json`](../../../../renovate.json). It is
based on [Renovate configuration for automated Drupal dependency updates](https://github.com/drevops/renovate-drupal)
project.

### Features

1. Dual schedules for Drupal package updates:
    - Daily update schedule for critical Drupal core and related packages created in
      the `deps/drupal-minor-patch-core` branch.
    - Weekly update schedule for all other packages created in
      the `deps/drupal-minor-patch-contrib` branch.
2. Docker images updates in the `deps/docker` branch.
3. GitHub Actions updates in the `deps/github-actions` branch.
4. Automatically adds a `dependencies` label to a pull request.
5. Automatically adds assignees to a pull request.
6. Configuration for running Renovate self-hosted instance using CircleCI.

### Self-hosted vs GitHub app

Renovate can run as a hosted GitHub app or as a standalone self-hosted service
in CircleCI.

A self-hosted service can be beneficial when your project is restricted in terms
of third-party access.
