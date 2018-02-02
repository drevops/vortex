# drupal-dev
Composer-based Drupal 8 project scaffolding with code linting, tests and automated builds (CI) integration.

[![CircleCI](https://circleci.com/gh/integratedexperts/drupal-dev/tree/8.x.svg?style=shield)](https://circleci.com/gh/integratedexperts/drupal-dev/tree/8.x)

[Drupal 7 version](https://github.com/integratedexperts/drupal-dev/tree/7.x)

## Requirements
- `php` and [Composer](https://getcomposer.org/) installed on your host machine
- [VirtualBox](https://www.virtualbox.org/) and [Vagrant](https://www.vagrantup.com/) installed on your host machine

## Usage
1. Create a blank project repository.
2. Download an archive of this project and extract into repository.
3. Run `./scripts/drupal-dev-init.sh` and follow the prompts.
![Project Init](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/project-init.png)
4. Commit all files to your repository and push.
5. To enable CI integration, login to [Circle CI](https://circleci.com/) with your GitHub account, go to "Projects" -> "Add project", select your new project from the list and click on "Setup project", select "2.0" box and click "Start building" button.
6. To start developing locally:
   - Copy your existing DB dump into `.data/db.dist.sql` OR run `mkdir .data && curl -L https://goo.gl/WFtJbT -o .data/db.dist.sql` to download minimal install Drupal 7 DB dump.
   - Run `composer build`.

## What is included
- Drupal 7 composer-based configuration
  - contrib modules management
  - libraries management
  - support for patches
- Custom core module scaffolding
- Custom theme scaffolding: Gruntfile, SASS/SCSS, globbing and Livereload.    
- Composer scripts to build and rebuild the project (one command used in all environments)
- PHP, JS and SASS code linting with pre-configured Drupal standards
- Behat testing configuration + usage examples 
- Integration with [Circle CI](https://circleci.com/) (2.0):
  - project full build (fully built Drupal site with production DB)
  - code linting
  - testing (including Selenium-based Behat tests)
  - **artefact deployment to [destination repository](https://github.com/integratedexperts/drupal-dev-destination)**

![Workflow](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/workflow.png)

## Build workflow
Automated build is orchestrated to run stages in separate containers, allowing to run tests in parallel and fail fast.

![CircleCI build workflow](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/circleci_build.png)

## Presentation

https://goo.gl/CRBFw2

## Useful projects

- [Behat Screenshot](https://github.com/integratedexperts/behat-screenshot) - Behat extension and a step definition to create HTML and image screenshots on demand or test fail.
- [Behat Progress Fail](https://github.com/integratedexperts/behat-format-progress-fail) - Behat output formatter to show progress as TAP and fails inline.
- [Behat Relativity](https://github.com/integratedexperts/behat-relativity) - Behat context for relative elements testing
- [Robo Artifact Builder](https://github.com/integratedexperts/robo-git-artefact) - Robo task to push git artefact to remote repository
-------------------------------------------------------------------------------
Remove this line and everything above it in your project.

# MYSITE
Drupal 7 implementation of MYSITE

[![CircleCI](https://circleci.com/gh/myorg/mysite.svg?style=shield)](https://circleci.com/gh/myorg/mysite)

## Local environment setup
1. Make sure that `composer`, `VirtualBox`, `Vagrant` and plugins are installed.
   - Install [Vagrant](https://www.vagrantup.com/downloads.html), [VirtualBox](https://www.virtualbox.org/wiki/Downloads) and [Composer](https://getcomposer.org/).
   - Install `vagrant-hostupdater` plugin:
     ```
     vagrant plugin install vagrant-hostsupdater
     ```
   - Install `vagrant-auto_network` plugin:
     ```
     vagrant plugin install vagrant-auto_network
     ```
2. Checkout project repo
3. Copy `db.sql` to `.data/db.sql`
4. `composer build`

## Available commands
- `drupal:build` - build project.
- `drupal:rebuild` - cleanup code dependencies and rebuild site, skipping VM management.
- `drupal:rebuild-full` - cleanup code dependencies, remove VM and rebuild the site.
- `drupal:build-db` - re-import DB and run updates.
- `drupal:build-fed` - build theme assets. Supports watching: `composer build-theme -- watch`.
- `drupal:cleanup` - remove code dependencies, skipping VM management.
- `drupal:cleanup-full` - remove code dependencies and remove VM.

- `docker:up` - start environment.
- `docker:restart` - restart environment.
- `docker:down` - stop environment preserving data.
- `docker:destroy` - destroy environment.
- `docker:pull` - pull latest images.
- `docker:cli` - run command in CLI container.
- `docker:logs` - show logs from docker compose run.
- `docker:prune` - remove all unused containers and images.

## Adding Drupal modules
`composer require drupal/module_name`

## Adding patches for drupal modules
1. Add `title` and `url` to patch on drupal.org to the `patches` array in `composer.json`.

```
"repositories": [
  {
    "type": "package",
    "package": {
      "name": "myorg/patches",
      "version": "1.0.0",
      "type": "metapackage",
      "extra": {
        "patches": {
          "drupal/persistent_update": [
            {
              "title": "Add an ability to bypass all persistent updates",
              "url": "https://www.drupal.org/files/issues/bypass-all-persistent-updates-2824493-3.patch"
            }
          ]
        }
      }
    }
  }
]
```

2. `composer rebuild`

One caveat is that a removal of composer.lock may be required before step #2 as composer caches dependencies and patches may not be applied.

## Coding standards
PHP and JS code linting uses [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) with Drupal rules from [Coder](https://www.drupal.org/project/coder) module and additional local overrides in `phpcs.xml.dist` and `.eslintrc`.   

SASS and SCSS code linting uses [Sass Lint](https://github.com/sasstools/sass-lint) with additional local overrides in `.sass-lint.yml`.

## Behat tests
Behat configuration uses multiple extensions: 
- [Drupal Behat Extension](https://github.com/jhedstrom/drupalextension) - Drupal integration layer. Allows to work with Drupal API from within step definitions.
- [Behat Screenshot Extension](https://github.com/integratedexperts/behat-screenshot) - Behat extension and a step definition to create HTML and image screenshots on demand or test fail.
- [Behat Progress Fail Output Extension](https://github.com/integratedexperts/behat-format-progress-fail) - Behat output formatter to show progress as TAP and fail messages inline. Useful to get feedback about failed tests while continuing test run.
- `FeatureContext` - Site-specific context with custom step definitions.  

## Automated builds (Continuous Integration)
In software engineering, continuous integration (CI) is the practice of merging all developer working copies to a shared mainline several times a day. 
Before feature changes can be merged into a shared mainline, a complete build must run and pass all tests on CI server.

This project uses [Circle CI](https://circleci.com/) as CI server: it imports production backups into fully built codebase and runs code linting and tests. When tests pass, a deployment process is triggered for nominated branches (usually, `master` and `develop`).

Add [skip ci] to the commit subject to skip CI build. Useful for documentation changes.

### SSH
Circle CI supports SSHing into the build for 30 minutes after the build is finished. SSH can be enabled either during the build run or when the build is started with SSH support.

### Cache
Circle CI supports caching between builds. The cache takes care of saving the state of your dependencies between builds, therefore making the builds run faster.
Each branch of your project will have a separate cache. If it is the very first build for a branch, the cache from the default branch on GitHub (normally `master`) will be used. If there is no cache for master, the cache from other branches will be used.
If the build has inconsistent results (build fails in CI but passes locally), try to re-running the build without cache by clicking 'Rebuild without cache' button.

### Test artifacts
Test artifacts (screenshots etc.) are available under 'Artifacts' tab in Circle CI UI.

### All Selenium tests are suddenly broken
Chromedriver (a binary used by Selenium to control Chrome browser) version must follow Chrome version. Currently, Chrome version is set to stable, so when there is an update of the major Chrome version, Chromedriver version needs to be updated as well. The following link contains information about currently available Chromedriver versions: http://chromedriver.storage.googleapis.com
