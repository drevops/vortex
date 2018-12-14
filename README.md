# Drupal-Dev [![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=%F0%9F%92%A7%2B+%F0%9F%90%B3%2B+%E2%9C%93%E2%9C%93%E2%9C%93+%2B+%F0%9F%A4%96+%3D+Drupal-Dev+-+open-source+development+practice+for+Drupal+projects&url=https://www.drupal-dev.io&via=integratedexperts&hashtags=drupal,workflow,composer,template,kickstart,ci,test,build)
Composer-based Drupal 8 project scaffolding with code linting, tests and automated builds (CI) integration.

![Drupal 8](https://img.shields.io/badge/Drupal-8-blue.svg)
[![Release](https://img.shields.io/github/release/integratedexperts/drupal-dev.svg)](https://github.com/integratedexperts/drupal-dev/releases/latest)
[![Licence: GPL 3](https://img.shields.io/badge/licence-GPL3-blue.svg)](https://github.com/integratedexperts/drupal-dev/blob/8.x/LICENSE)
[![CircleCI](https://circleci.com/gh/integratedexperts/drupal-dev/tree/8.x.svg?style=shield)](https://circleci.com/gh/integratedexperts/drupal-dev/tree/8.x)

**Looking for Drupal 8 version?**
[Click here to switch to Drupal 8 version](https://github.com/integratedexperts/drupal-dev/tree/8.x)

![Workflow](.drupal-dev/images/workflow.png)

## Getting started
1. Create a blank project repository.
2. Download an archive of this project and extract into the repository directory.
3. Run `ahoy init` and follow the prompts. **DO NOT SKIP THIS STEP!**
4. Commit all files to your repository and push.
5. Refer to created README.md file in your project.

## Typical development workflow
1. Download fresh copy of the DB: `ahoy download-db`
2. Start `pygmy`: `pygmy up`
3. Build project: `ahoy build` 

## What is included
- Drupal 8 Composer-based configuration:
  - contrib modules management
  - libraries management
  - support for patches
  - development and testing tools
- Custom core module scaffolding
- Custom theme scaffolding: Gruntfile, SASS/SCSS, globbing and Livereload.    
- `ahoy` commands to build and rebuild the project (consistent commands used in all environments).
- PHP, JS and SASS code linting with pre-configured Drupal standards
- Behat testing configuration + usage examples 
- Integration with [Circle CI](https://circleci.com/) (2.0):
  - project full build (fully built Drupal site with production DB)
  - code linting
  - testing (including Selenium-based Behat tests)
  - **artefact deployment to [destination repository](https://github.com/integratedexperts/drupal-dev-destination)**
- Integration with [dependencies.io](https://dependencies.io) to keep the project up-to-date.
- Integration with Acquia Cloud.
- Integration with [Lagoon](https://github.com/amazeeio/lagoon).
- Project documentation [template](.drupal-dev/README.template.md)
- GitHub templates.
- Project initialisation script
- Drupal-dev has own suit of automated tests.

![Project Initialisation](.drupal-dev/images/project-init.png)

## Build workflow
Automated build is orchestrated to run stages in separate containers, allowing to run tests in parallel and fail fast.

![CircleCI build workflow](.drupal-dev/images/circleci_build.png)

## FAQs

## Why [`Ahoy`](https://github.com/ahoy-cli/ahoy)?
- Consistent commands across projects - unified Developer Experience (DX).
- Standalone file that can be easily copied across projects.
- Simple YAML syntax. 
- Workflow is no longer captured in places that were not designed for it: Composer scripts, NPM scripts etc.

## Why not Lando, DDEV, Docksal?
- Running the same workflow commands in Local and CI is a paramount.
- Current solution is pure Docker/Docker Compose and does not require any additional configuration generators.
- No dependency on additional tool.

## Why use `amazeeio` containers?
- [Amazee.io](https://www.amazee.io/) maintain their containers as they are powering their open-source hosting platform [Lagoon](https://github.com/amazeeio/lagoon).
- Changes to containers are fully tested with every change using CI systems (part of Lagoon).
- Containers are production-ready.

## Why CircleCI?
- Very fast.
- Supports workflow.
- Supports parallelism.
- Provides remote Docker engine to run and build containers with layer caching.
- Allows customising build runner container.
- Flexible [pricing model](https://circleci.com/pricing/) (for proprietary projects). Free for open-source.

## Why dependencies.io?
- Configurable runners for different types of dependencies (PHP, JS, Ruby etc).
- Configurable base branch, new branch prefixes and assigned labels.
- Supports pre- and post-update hooks. 
- Flexible [pricing model](https://www.dependencies.io/pricing/) for proprietary projects.

# Contributing
- Progress is tracked as [GitHub project](https://github.com/integratedexperts/drupal-dev/projects/1). 
- Development takes place in 2 independent branches named after Drupal core version: `7.x` or `8.x`.
- Create issue and prefix title with Drupal core version: `[7.x] Updated readme file.`. 
- Create PRs with branches prefixed with Drupal core version: `7.x` or `8.x`. For example, `feature/7.x-updated-readme`.

# Paid support
[Integrated Experts](https://github.com/integratedexperts) can provide support for Drupal-Dev in your organisation: 
- New and existing project onboarding.
- Support plans with SLAs.
- Priority feature implementation.
- Updates to the latest version of the platform.

Contact us at [support@integratedexperts.com](mailto:support@integratedexperts.com)

## Useful projects

- [Robo Artifact Builder](https://github.com/integratedexperts/robo-git-artefact) - Robo task to push git artefact to remote repository
- [Behat Steps](https://github.com/integratedexperts/behat-steps) - Collection of Behat step definitions.
- [Behat Screenshot](https://github.com/integratedexperts/behat-screenshot) - Behat extension and a step definition to create HTML and image screenshots on demand or test fail.
- [Behat Progress Fail](https://github.com/integratedexperts/behat-format-progress-fail) - Behat output formatter to show progress as TAP and fails inline.
- [Behat Relativity](https://github.com/integratedexperts/behat-relativity) - Behat context for relative elements testing
