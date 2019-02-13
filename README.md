[//]: # (#< DRUPAL-DEV)
# Drupal-Dev [![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=%F0%9F%92%A7%2B+%F0%9F%90%B3%2B+%E2%9C%93%E2%9C%93%E2%9C%93+%2B+%F0%9F%A4%96+%3D+Drupal-Dev+-+open-source+development+practice+for+Drupal+projects&url=https://www.drupal-dev.io&via=integratedexperts&hashtags=drupal,workflow,composer,template,kickstart,ci,test,build)
Composer-based Drupal 8 project scaffolding with code linting, tests and automated builds (CI) integration.

![Drupal 8](https://img.shields.io/badge/Drupal-8-blue.svg)
[![Release](https://img.shields.io/github/release/integratedexperts/drupal-dev.svg)](https://github.com/integratedexperts/drupal-dev/releases/latest)
[![Licence: GPL 3](https://img.shields.io/badge/licence-GPL3-blue.svg)](https://github.com/integratedexperts/drupal-dev/blob/8.x/LICENSE)
[![CircleCI](https://circleci.com/gh/integratedexperts/drupal-dev/tree/8.x.svg?style=shield)](https://circleci.com/gh/integratedexperts/drupal-dev/tree/8.x)

**Looking for Drupal 8 version?**
[Click here to switch to Drupal 8 version](https://github.com/integratedexperts/drupal-dev/tree/8.x)

![Workflow](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/workflow.png)

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
- Project documentation [template](README.md)
- GitHub templates.
- Project initialisation script
- Drupal-dev has own suit of automated tests.

![Project Initialisation](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/project-init.png)

## Build workflow
Automated build is orchestrated to run stages in separate containers, allowing to run tests in parallel and fail fast.

![CircleCI build workflow](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/circleci_build.png)

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

**Below is a contents of the `README.md` file that will be added to your project.**

[//]: # (#> DRUPAL-DEV)
# MYSITE
Drupal 8 implementation of MYSITE

[![CircleCI](https://circleci.com/gh/myorg/mysite.svg?style=shield)](https://circleci.com/gh/myorg/mysite)

## Local environment setup
1. Make sure that you have latest versions of all required software installed:   
  - [Docker](https://www.docker.com/) 
  - [Pygmy](https://docs.amazee.io/local_docker_development/pygmy.html)
  - [Ahoy](https://github.com/ahoy-cli/ahoy)
2. Make sure that all local web development services are shut down (apache/nginx, mysql, MAMP etc).
3. Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/docker-for-mac/osxfs/#access-control)).  
[//]: # (#< ACQUIA)
4. Add Acquia Cloud credentials to ".env.local" file:
```
  # Acquia Cloud UI->Account->Credentials->Cloud API->E-mail
  AC_API_USER_NAME=<YOUR_USERNAME>
  # Acquia Cloud UI->Account->Credentials->Cloud API->Private key
  AC_API_USER_PASS=<YOUR_TOKEN>
```
[//]: # (#> ACQUIA)
5. `ahoy download-db`
6. `pygmy up`
7. `ahoy build`

## Available `ahoy` commands
Run each command as `ahoy <command>`.
  ```  
   build        Build or rebuild project.
   clean        Remove all build files.
   clean-full   Remove all development files.
   cli          Start a shell inside CLI container or run a command.
   down         Stop Docker containers and remove container, images, volumes and networks.
   download-db  Download database.
   doctor       Find problems with current project setup.
   drush        Run drush commands in the CLI service container.
   export-db    Export database dump.
   fe           Build front-end assets.
   fed          Build front-end assets for development.
   few          Watch front-end assets during development.
   flush-redis  Flush Redis cache.
   info         Print information about this project.
   install-site Install a site.
   lint         Lint code.
   login        Login to a website.
   logs         Show Docker logs.
   pull         Pull latest docker images.
   restart      Restart all stopped and running Docker containers.
   start        Start existing Docker containers.
   stop         Stop running Docker containers.
   test         Run all tests.
   test-behat   Run Behat tests.
   test-phpunit Run PHPUnit tests.
   up           Build and start Docker containers.
  ```

## Adding Drupal modules

`composer require drupal/module_name`

## Adding patches for drupal modules

1. Add `title` and `url` to patch on drupal.org to the `patches` array in `extra` section in `composer.json`.

```
    "extra": {
        "patches": {
            "drupal/core": {
                "Contextual links should not be added inside another link - https://www.drupal.org/node/2898875": "https://www.drupal.org/files/issues/contextual_links_should-2898875-3.patch"
            }
        }    
    }
```

2. `composer update --lock`

## Front-end and Livereload
- `npm run build` - build SCSS and JS assets.
- `npm run watch` - watch asset changes and reload the browser (using Livereload). To enable Livereload integration with Drupal, add to `settings.php` file (already added to `settings.local.php`): 
  ```
  $conf['livereload'] = TRUE;
  ```

## Coding standards
PHP and JS code linting uses [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) with Drupal rules from [Coder](https://www.drupal.org/project/coder) module and additional local overrides in `phpcs.xml` and `.eslintrc.json`.   

SASS and SCSS code linting use [Sass Lint](https://github.com/sasstools/sass-lint) with additional local overrides in `.sass-lint.yml`.

## Behat tests
Behat configuration uses multiple extensions: 
- [Drupal Behat Extension](https://github.com/jhedstrom/drupalextension) - Drupal integration layer. Allows to work with Drupal API from within step definitions.
- [Behat Screenshot Extension](https://github.com/integratedexperts/behat-screenshot) - Behat extension and a step definition to create HTML and image screenshots on demand or test fail.
- [Behat Progress Fail Output Extension](https://github.com/integratedexperts/behat-format-progress-fail) - Behat output formatter to show progress as TAP and fail messages inline. Useful to get feedback about failed tests while continuing test run.
- `FeatureContext` - Site-specific context with custom step definitions.

Add `@skipped` tag to failing tests if you would like to skop them.  

## Automated builds (Continuous Integration)
In software engineering, continuous integration (CI) is the practice of merging all developer working copies to a shared mainline several times a day. 
Before feature changes can be merged into a shared mainline, a complete build must run and pass all tests on CI server.

This project uses [Circle CI](https://circleci.com/) as CI server: it imports production backups into fully built codebase and runs code linting and tests. When tests pass, a deployment process is triggered for nominated branches (usually, `master` and `develop`).

Add `[skip ci]` to the commit subject to skip CI build. Useful for documentation changes.

### SSH
Circle CI supports SSHing into the build for 120 minutes after the build is finished when the build is started with SSH support. Use "Rerun job with SSH" button in Circle CI UI to start build with SSH support.

### Cache
Circle CI supports caching between builds. The cache takes care of saving the state of your dependencies between builds, therefore making the builds run faster.
Each branch of your project will have a separate cache. If it is the very first build for a branch, the cache from the default branch on GitHub (normally `master`) will be used. If there is no cache for master, the cache from other branches will be used.
If the build has inconsistent results (build fails in CI but passes locally), try to re-running the build without cache by clicking 'Rebuild without cache' button.

### Test artifacts
Test artifacts (screenshots etc.) are available under "Artifacts" tab in Circle CI UI.

[//]: # (#< DEPLOYMENT)
## Deployment
Please refer to [DEPLOYMENT.md](DEPLOYMENT.md)
[//]: # (#> DEPLOYMENT) 

## FAQs
Please see [FAQs](FAQs.md)
