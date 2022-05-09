[//]: # (#;< DREVOPS_DEV)
<p align="center">
	<img width="400" src="https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/drevops_logo_text_white.png" alt="DrevOps Logo" />
</div>
<h3 align="center">Build, Test, Deploy scripts for Drupal using Docker and CI/CD</h3>
<div align="center">

[![CircleCI](https://circleci.com/gh/drevops/drevops/tree/9.x.svg?style=shield)](https://circleci.com/gh/drevops/drevops/tree/9.x)
![Drupal 9](https://img.shields.io/badge/Drupal-9-blue.svg)
[![Licence: GPL 3](https://img.shields.io/badge/licence-GPL3-blue.svg)](https://github.com/drevops/drevops/blob/9.x/LICENSE)
[![Dependencies.io](https://img.shields.io/badge/dependencies.io-enabled-green.svg)](https://dependencies.io)
[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=%F0%9F%92%A7%2B%20%F0%9F%90%B3%20%2B%20%E2%9C%93%E2%9C%93%E2%9C%93%20%2B%20%F0%9F%A4%96%20%3D%20DrevOps%20-%20%20Build%2C%20Test%2C%20Deploy%20scripts%20for%20Drupal%20using%20Docker%20and%20CI%2FCD&amp;url=https://www.drevops.com&amp;via=drev_ops&amp;hashtags=drupal,devops,workflow,composer,template,kickstart,ci,test,build)

</div>

--------------------------------------------------------------------------------

Visit [Documentation](https://docs.drevops.com) site for more information.

--------------------------------------------------------------------------------

## About DrevOps

### What is the problem that DrevOps is trying to solve?
Increase the quality of the produced Drupal websites with minimum setup effort
and knowledge.

### Why is this problem important?
High quality Drupal websites are stable, secure, faster and safer to change.

But developers do not always have the time or skills to setup the tools required
to produce and maintain high-quality Drupal websites.

### How does DrevOps solve it?
Quick installation of best practices Drupal configuration on Docker stack using
a single command lowers entry barrier, while unification of the developer's
experience improves development speed across projects.

### Who is it for?
- Digital agencies that want to standardise their development stack (standard
  operating environment) across projects
- Developers that are looking for best practices
- Developers that do not possess required time or knowledge to setup these tools
  themselves

### How does it work?
- You run installer script once
- DrevOps brings the latest release into your codebase
- You commit all new files
- If required, you may override files with changes relevant only to a specific
  project.

## Installation

1. Run [installer](https://github.com/drevops/drevops/blob/9.x/install.php):
   ```
   curl -SsL http://install.drevops.com | php
   ```
2. Commit added files.

3. Follow instructions in the generated `README.md` files of your project.

## Contributing

- Progress is tracked as [GitHub project](https://github.com/drevops/drevops/projects/1).
- Development takes place in 3 independent branches named after Drupal core
  version: `9.x`, `8.x` and `7.x`.
- Create an issue and prefix title with Drupal core version: `[9.x] Updated
  readme file.`.
- Create PRs with branches prefixed with Drupal core version: `9.x`, `8.x` or
  `7.x`. For example, `feature/9.x-updated-readme`.

--------------------------------------------------------------------------------

Visit [Documentation](https://docs.drevops.com) site for more information.

--------------------------------------------------------------------------------

## Paid support

We provide paid support for DrevOps:
- New and existing project onboarding.
- Support plans with SLAs.
- Priority feature implementation.
- Updates to the latest version of the platform.
- DevOps consulting and custom implementations.

Contact us at [support@drevops.com](mailto:support@drevops.com)

## Useful projects

- [Drupal module testing in CircleCI](https://github.com/integratedexperts/drupal_circleci)
- [MariaDB Docker image with enclosed data](https://github.com/drevops/mariadb-drupal-data) - Docker image to capture database data as a Docker layer.
- [CI Builder Docker image](https://github.com/drevops/ci-builder) - Docker image for CI builder container with many pre-installed tools.
- [Behat Steps](https://github.com/drevops/behat-steps) - Collection of Behat step definitions.
- [Behat Screenshot](https://github.com/drevops/behat-screenshot) - Behat extension and a step definition to create HTML and image screenshots on demand or test fail.
- [Behat Progress Fail](https://github.com/drevops/behat-format-progress-fail) - Behat output formatter to show progress as TAP and fails inline.
- [Behat Relativity](https://github.com/drevops/behat-relativity) - Behat context for relative elements testing.
- [Artifact Builder](https://github.com/drevops/git-artifact) - Build code artifact and push it to remote repository.

--------------------------------------------------------------------------------
**Below is a content of the `README.md` file that will be added to your project.**

**All content above this line will be automatically removed during installation.**

[//]: # (#;> DREVOPS_DEV)
# YOURSITE
Drupal 9 implementation of YOURSITE for YOURORG

[![CircleCI](https://circleci.com/gh/your_org/your_site.svg?style=shield)](https://circleci.com/gh/your_org/your_site)
![Drupal 9](https://img.shields.io/badge/Drupal-9-blue.svg)

[//]: # (#;< DEPENDENCIESIO)

[![Dependencies.io](https://img.shields.io/badge/dependencies.io-enabled-green.svg)](https://dependencies.io)

[//]: # (#;> DEPENDENCIESIO)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY DREVOPS TO TRACK INTEGRATION)

[![DrevOps](https://img.shields.io/badge/DrevOps-DREVOPS_VERSION_URLENCODED-blue.svg)](https://github.com/drevops/drevops/tree/DREVOPS_VERSION)

[//]: # (Remove the section below once onboarding is finished)
## Onboarding
Use [Onboarding checklist](ONBOARDING.md) to track the project onboarding progress.

## Local environment setup
- Make sure that you have latest versions of all required software installed:
  - [Docker](https://www.docker.com/)
  - [Pygmy](https://github.com/pygmystack/pygmy)
  - [Ahoy](https://github.com/ahoy-cli/ahoy)
- Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
- Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/docker-for-mac/osxfs/#access-control)).

[//]: # (#;< ACQUIA)

- Authenticate with Acquia Cloud API
  1. Create your Acquia Cloud API token:<br/>
     Acquia Cloud UI -> Account -> API tokens -> Create Token
  2. Copy `default.env.local` to `.env.local`.
  3. Populate `$DREVOPS_ACQUIA_KEY` and `$DREVOPS_ACQUIA_SECRET` environment
     variables in `.env.local` file with values generated in the step above.

[//]: # (#;> ACQUIA)

[//]: # (#;< LAGOON)

- Authenticate with Lagoon
  1. Create an SSH key and add it to your account in the [Lagoon Dashboard](https://ui-lagoon-master.ch.amazee.io/).
  2. Copy `default.env.local` to `.env.local`.
  3. Update `$DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE` environment variable in `.env.local` file
  with the path to the SSH key.

[//]: # (#;> LAGOON)


[//]: # (#;< !FRESH_INSTALL)

- `ahoy download-db`

[//]: # (#;> !FRESH_INSTALL)
- `pygmy up`
- `ahoy build`

### Apple M1 adjustments

Copy `default.docker-compose.override.yml` to `docker-compose.override.yml`.

## Testing
Please refer to [testing documentation](TESTING.md).

## CI
Please refer to [CI documentation](CI.md).

[//]: # (#;< DEPLOYMENT)

## Deployment
Please refer to [deployment documentation](DEPLOYMENT.md).

[//]: # (#;> DEPLOYMENT)

## Releasing
Please refer to [releasing documentation](RELEASING.md).

## FAQs
Please refer to [FAQs](FAQs.md).
