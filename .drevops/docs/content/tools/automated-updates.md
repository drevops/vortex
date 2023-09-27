# 🔄 Automated updates

DrevOps uses [Renovate](https://renovatebot.com) for automated dependency updates.

The configuration is stored in [`renovate.json`](../../../../renovate.json). It is
based on [Renovate configuration for automated Drupal dependency updates](https://github.com/drevops/renovate-drupal)
project.

### Features

1. Dual schedules for package updates:
    - Daily update schedule for critical Drupal core and related packages created in
      the `deps/minor-patch-core` branch.
    - Weekly update schedule for all other packages created in
      the `deps/minor-patch-contrib` branch.
2. Automatically adds a `dependencies` label to a pull request.
3. Automatically adds assignees to a pull request.
4. Configuration for running Renovate self-hosted instance using CircleCI.

### Self-hosted vs GitHub app

Renovate can run as a hosted GitHub app or as a standalone self-hosted service
in CircleCI.

A self-hosted service can be beneficial when your project is restricted in terms
of third-party access.
