# MYSITE
Drupal 8 implementation of MYSITE

[![CircleCI](https://circleci.com/gh/myorg/mysite.svg?style=shield)](https://circleci.com/gh/myorg/mysite)

## Local environment setup
1. Make sure that you have `make`, [Docker](https://www.docker.com/) and [Pygmy](https://docs.amazee.io/local_docker_development/pygmy.html) installed.
2. Checkout project repo
3. (optional) Copy local settings and services files from defaults:
    ```
    cp docroot/sites/default/default.settings.local.php docroot/sites/default/settings.local.php
    cp docroot/sites/default/default.services.local.yml docroot/sites/default/services.local.yml
    ```
3. `make download-db`
4. `pygmy up`
5. `make build`

## Available make commands
Run each command as `make <command>`.
  ```
  build                Build project dependencies.
  build-fed            Build front-end assets.
  build-fed-prod       Build front-end assets for production.
  clean                Remove dependencies.
  clean-full           Remove dependencies and Docker images.
  docker-cli           Execute command inside of CLI container.
  docker-destroy       Destroy Docker containers.
  docker-logs          Show logs.
  docker-pull          Pull newest base images.
  docker-restart       Re-start Docker containers.
  docker-start         Start Docker containers.
  docker-stop          Stop Docker containers.
  download-db          Download database.
  drush                Run Drush command.
  export-db-dump       Export database dump.
  help                 Display this help message.
  import-db            Import database dump and run post import commands.
  import-db-dump       Import database dump.
  lint                 Lint code.
  login                Login to the website.
  rebuild              Re-build project dependencies.
  rebuild-full         Cleanup and fully re-build project dependencies.
  sanitize-db          Sanitize database.
  site-install         Install a site.
  test                 Run all tests.
  test-behat           Run Behat tests.
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

[]([META:DEPLOYMENT])
## Deployment
Please refer to [DEPLOYMENT.md](DEPLOYMENT.md)
[]([/META:DEPLOYMENT]) 

## FAQs
Please see [FAQs] (FAQs.md)
