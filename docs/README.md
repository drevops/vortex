## What is DrevOps?
- Drupal project scaffolding template
- Docker stack configuration
- CI configuration and tools
- Production hosting integration configuration
- Documentation repository
- “Glue code” to keep it all together

## What DrevOps is NOT
- A replacement for Lando, DDEV, Tokaido etc.
- A (yet another) custom docker images repository
- A hosting provider
- A CI system
- Paid SaaS or PaaS service

## Approach

Identical configuration for all environments.

![CI banner](https://raw.githubusercontent.com/wiki/drevops/drevops/images/drevops_ci_banner.png)

## Workflow

![Workflow](https://raw.githubusercontent.com/wiki/drevops/drevops/images/workflow.png)

## Features
- **Highly configurable**  <br/>
  All configuration is made through environment variables without the need to change any of the scripts.
- **Flexible**<br/>
  Anything can be overridden for your specific project and will not be lost during the next update.
- **Pure Docker stack**  <br/>
  No special binaries to work with Docker. No generated Docker Compose configurations from custom configurations. Modify configuration to fit your project needs.
- **Tested**  <br/>
  There are tests for workflows, configuration, deployments, CI. Everything. And they run automatically on every commit.
- **Versioned**  <br/>
  It is always clear which version of the stack your site uses.
- **Upgradable**  <br/>
  Your website can be updated to a newer version of DrevOps with minimal effort. Your project customisations are easily preserved.
- **Documented**  <br/>
  The major areas of DrevOps are explicitly documented, while most of the code is self-documented.

--------------------------------------------------------------------------------

## DrupalSouth 2019 presentation

<div style="text-align: center;"> 
  <div class="embed-container">
    <iframe src="https://www.youtube.com/embed/zOsjoWzMvmc" width="700" height="480" frameborder="0" allowfullscreen=""></iframe>
  </div>
</div>

--------------------------------------------------------------------------------

## Contributing
- Progress is tracked in [GitHub project](https://github.com/drevops/drevops/projects/1). 
- Development takes place in 2 independent branches named after Drupal core version: [`7.x`](https://github.com/drevops/drevops/tree/7.x) or [`8.x`](https://github.com/drevops/drevops/tree/8.x).
- [Create an issue](https://github.com/drevops/drevops/issues/new) and prefix title with Drupal core version: `[8.x] Updated readme file.`. 
- Create PRs with branches prefixed with Drupal core version: `7.x` or `8.x`. For example, `feature/8.x-updated-readme`.

--------------------------------------------------------------------------------

## Other projects

- [Drupal module testing in CircleCI](https://github.com/integratedexperts/drupal_circleci)
- [CI Builder Docker image](https://github.com/integratedexperts/ci-builder) - Docker image for CI builder container with many pre-installed tools.
- [Code Artifact Builder](https://github.com/integratedexperts/robo-git-artefact) - Robo task to push git artefact to remote repository.
- [GitHub Labels](https://github.com/integratedexperts/github-labels) - Shell script to create labels on GitHub.
- [Behat Steps](https://github.com/integratedexperts/behat-steps) - Collection of Behat step definitions.
- [Behat Screenshot](https://github.com/integratedexperts/behat-screenshot) - Behat extension and a step definition to create HTML and image screenshots on demand or test fail.
- [Behat Progress Fail](https://github.com/integratedexperts/behat-format-progress-fail) - Behat output formatter to show progress as TAP and fails inline. 
