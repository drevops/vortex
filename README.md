# drupal-dev
Composer-based Drupal 8 project scaffolding with code linting, tests and automated builds (CI) integration.

[![CircleCI](https://circleci.com/gh/integratedexperts/drupal-dev/tree/8.x.svg?style=shield)](https://circleci.com/gh/integratedexperts/drupal-dev/tree/8.x)

[Click here to switch to Drupal 7 version](https://github.com/integratedexperts/drupal-dev/tree/7.x)

## Usage
1. Create a blank project repository.
2. Download an archive of this project and extract into repository directory.
3. **Run `. .dev/init.sh` and follow the prompts.** DO NOT SKIP THIS STEP!
![Project Init](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/project-init.png)
4. Commit all files to your repository and push.
5. To enable CI integration, login to [Circle CI](https://circleci.com/) with your GitHub account, go to "Projects" -> "Add project", select your new project from the list and click on "Setup project" and click "Start building" button.
6. To start developing locally:
   - Make sure that you have `make`, [composer](https://getcomposer.org/), [Docker](https://www.docker.com/) and [Pygmy](https://docs.amazee.io/local_docker_development/pygmy.html) installed.
   - Copy your existing DB dump into `.data/db.sql` OR download minimal install Drupal 7 DB dump:    
     ```
     make download-db
     ```
   - Run `make build`.

## What is included
- Drupal 8 composer-based configuration
  - contrib modules management
  - libraries management
  - support for patches
- Custom core module scaffolding
- Custom theme scaffolding: Gruntfile, SASS/SCSS, globbing and Livereload.    
- `make` scripts to build and rebuild the project (one command used in all environments)
- PHP, JS and SASS code linting with pre-configured Drupal standards
- Behat testing configuration + usage examples 
- Integration with [Circle CI](https://circleci.com/) (2.0):
  - project full build (fully built Drupal site with production DB)
  - code linting
  - testing (including Selenium-based Behat tests)
  - **artefact deployment to [destination repository](https://github.com/integratedexperts/drupal-dev-destination)**
- Integration with [dependencies.io](https://dependencies.io) to keep the project up-to-date.
- Project documentation [template](.dev/README.template.md) 

![Workflow](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/workflow.png)

## Build workflow
Automated build is orchestrated to run stages in separate containers, allowing to run tests in parallel and fail fast.

![CircleCI build workflow](https://raw.githubusercontent.com/wiki/integratedexperts/drupal-dev/images/circleci_build.png)

## FAQs
Please see [FAQs file](FAQs.md)

## Presentation

https://goo.gl/CRBFw2

## Useful projects

- [Behat Screenshot](https://github.com/integratedexperts/behat-screenshot) - Behat extension and a step definition to create HTML and image screenshots on demand or test fail.
- [Behat Progress Fail](https://github.com/integratedexperts/behat-format-progress-fail) - Behat output formatter to show progress as TAP and fails inline.
- [Behat Relativity](https://github.com/integratedexperts/behat-relativity) - Behat context for relative elements testing
- [Robo Artifact Builder](https://github.com/integratedexperts/robo-git-artefact) - Robo task to push git artefact to remote repository
