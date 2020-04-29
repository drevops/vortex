## What is DrevOps?
- Build, Test, Deploy scripts
- Docker stack
- CI/CD configuration
- Hosting integration
- Documentation repository

## What DrevOps is NOT
- A replacement for Lando, DDEV, Tokaido etc.
- A (yet another) custom docker images repository
- A hosting provider
- A CI system
- Paid SaaS or PaaS service

## Features
- **Highly configurable**  <br/>
  All configuration is made through environment variables without the need to change any of the scripts.
- **Flexible**<br/>
  Anything can be overridden for your specific project and will not be lost during the next update.
- **Pure Docker stack**  <br/>
  No special binaries to work with Docker. No generated Docker Compose configurations from custom configurations. Modify the configuration to fit your project needs.
- **Tested**  <br/>
  There are tests for workflows, configuration, deployments, CI. Everything. And they run automatically on every commit.
- **Versioned**  <br/>
  It is always clear which version of the stack your site uses.
- **Upgradable**  <br/>
  Your website can be updated to a newer version of DrevOps with minimal effort. Your project customisations are easily preserved.
- **Documented**  <br/>
  The major areas of DrevOps are explicitly documented, while most of the code is self-documented.

## Workflow

![Workflow](https://raw.githubusercontent.com/wiki/drevops/drevops/images/workflow.png)

--------------------------------------------------------------------------------

## DrupalSouth 2019 presentation

<div style="text-align: center;"> 
  <div class="embed-container">
    <iframe src="https://www.youtube.com/embed/zOsjoWzMvmc" width="700" height="480" frameborder="0" allowfullscreen=""></iframe>
  </div>
</div>

--------------------------------------------------------------------------------

## Paid support
[Integrated Experts](https://github.com/integratedexperts) provides paid support for DrevOps: 
- New and existing project onboarding.
- Support plans with SLAs.
- Priority feature implementation.
- Updates to the latest version of the platform.
- DevOps consulting and custom implementations.

Contact us at [support@integratedexperts.com](mailto:support@integratedexperts.com)

--------------------------------------------------------------------------------

## Other projects

- [Drupal module testing in CircleCI](https://github.com/integratedexperts/drupal_circleci)
- [MariaDB Docker image with enclosed data](https://github.com/drevops/mariadb-drupal-data) - Docker image to capture database data as a Docker layer.
- [CI Builder Docker image](https://github.com/drevops/ci-builder) - Docker image for CI builder container with many pre-installed tools.
- [Code Artifact Builder](https://github.com/integratedexperts/robo-git-artefact) - Robo task to push git artefact to remote repository.
- [GitHub Labels](https://github.com/integratedexperts/github-labels) - Shell script to create labels on GitHub.
- [Behat Steps](https://github.com/integratedexperts/behat-steps) - Collection of Behat step definitions.
- [Behat Screenshot](https://github.com/integratedexperts/behat-screenshot) - Behat extension and a step definition to create HTML and image screenshots on demand or test fail.
- [Behat Progress Fail](https://github.com/integratedexperts/behat-format-progress-fail) - Behat output formatter to show progress as TAP and fails inline.
