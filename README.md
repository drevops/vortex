[//]: # (#;< DREVOPS_DEV)
<table align="center"><tr><td>

<div align="center">
	<img width="400" src="https://user-images.githubusercontent.com/378794/228488082-814a8ddc-e749-4031-874c-ef545ac00cec.png" alt="DrevOps Logo" />
</div>

<h3 align="center">Drupal project template</h3>
<h4 align="center"><em>Onboarding in minutes, not hours or days!</em></h4>

<div align="center">

[![CircleCI](https://circleci.com/gh/drevops/drevops.svg?style=shield)](https://circleci.com/gh/drevops/drevops)
[![codecov](https://codecov.io/gh/drevops/drevops/graph/badge.svg?token=YDTAEWWT5H)](https://codecov.io/gh/drevops/drevops)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/drevops)
![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)
![LICENSE](https://img.shields.io/github/license/drevops/drevops)
[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=%F0%9F%92%A7%2B%20%F0%9F%90%B3%20%2B%20%E2%9C%93%E2%9C%93%E2%9C%93%20%2B%20%F0%9F%A4%96%20%3D%20DrevOps%20-%20%20Build%2C%20Test%2C%20Deploy%20scripts%20for%20Drupal%20using%20Docker%20and%20CI%2FCD&amp;url=https://www.drevops.com&amp;via=drev_ops&amp;hashtags=drupal,devops,workflow,composer,template,kickstart,ci,test,build)

</div>

</td></tr><tr><td>

## Purpose

Make it easy to set up, develop and support high-quality Drupal websites

## Approach

Use **tested** Drupal project template with DevOps integrations for CI and hosting platforms.

## How it works

1. You run the installer script once.
2. DrevOps integrates the latest project template release into your codebase.
3. You choose which changes to commit.

</td></tr><tr><td>

## Installation

    curl -SsL https://install.drevops.com | php

</td></tr><tr><td>

## Documentation

https://docs.drevops.com

<br/>

</td></tr><tr><td>

## Features

The following list includes ✅ completed and 💡 upcoming features.

* 💧 Drupal
  * ✅ Based on [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project)
  * ✅ [Drupal 10](https://www.drupal.org/project/drupal)
  * ✅ Configurable webroot
  * ✅ [Pre-configured settings](web/sites/default/settings.php)
  * ✅ [Custom module scaffold](web/modules/custom/ys_core)
  * ✅ [Custom theme scaffold](web/themes/custom/your_site_theme)
  * ✅ [Tests scaffold](web/modules/custom/ys_core/tests)
  * ✅ Configuration for admin modules: [Environment indicator](https://www.drupal.org/project/environment_indicator), [Shield](https://www.drupal.org/project/shield), [Stage file proxy](https://www.drupal.org/project/stage_file_proxy)
  * ✅ Configuration for [Search API](https://www.drupal.org/project/search_api) ([Solr](https://www.drupal.org/project/search_api_solr))
  * ✅ Configuration for caching ([Redis](https://www.drupal.org/project/redis))
  * ✅ Configuration for antivirus ([ClamAV](https://www.drupal.org/project/clamav))
* 🐳 Docker services
  * ✅ Nginx
  * ✅ PHP
  * ✅ MariaDB
  * ✅ Solr
  * ✅ Redis
  * ✅ ClamAV
  * ✅ Chrome
* 🏨 Hosting
  * ✅ [Acquia](https://www.acquia.com/)
  * ✅ [Lagoon](https://github.com/uselagoon/lagoon)
  * 💡 [Pantheon](https://pantheon.io/)
  * 💡 [Platform.sh](https://platform.sh/)
* 💻 Local development
  * ✅ [Docker Compose](https://docs.docker.com/compose/) + [Ahoy](https://github.com/ahoy-cli/ahoy)
  * 💡 [Lando](https://lando.dev/)
  * 💡 [DDEV](https://ddev.readthedocs.io/)
* 🏗️ CI/CD
  * ✅ [Circle CI](https://circleci.com/)
  * 💡 [GitHub Actions](https://github.com/features/actions)
  * 💡 [GitLab CI](https://docs.gitlab.com/ee/ci/)
  * 💡 [Azure Pipelines](https://azure.microsoft.com/en-us/services/devops/pipelines/)
* 🛠️ Tooling
  * ✅ [Behat](https://docs.behat.org/en/latest/) + [Drupal extension](https://github.com/jhedstrom/drupalextension) + [Behat Screenshot](https://github.com/drevops/behat-screenshot) + [Behat steps](https://github.com/drevops/behat-steps)
  * ✅ [ESLint](https://eslint.org/)
  * ✅ [PHP Parallel Lint](https://github.com/php-parallel-lint/PHP-Parallel-Lint)
  * ✅ [PHPCS](https://github.com/squizlabs/PHP_CodeSniffer)
  * ✅ [PHPMD](https://phpmd.org/)
  * ✅ [PHPStan](https://github.com/phpstan/phpstan)
  * ✅ [PHPUnit](https://phpunit.de/)
  * ✅ [SASS Lint](https://github.com/sasstools/sass-lint)
  * ✅ [SASS](https://sass-lang.com/)
  * ✅ [Twigcs](https://github.com/friendsoftwig/twigcs)
  * 💡 [Pa11y](https://pa11y.org/)
* ⚙️ Workflow
  * ✅ Database from FTP, CURL, Docker image, hosting provider
  * ✅ [Pull request template](.github/PULL_REQUEST_TEMPLATE.md)
  * ✅ [Release drafter](https://github.com/release-drafter/release-drafter)
  * ✅ [PR auto-assign](https://github.com/toshimaru/auto-author-assign)
  * ✅ [PR auto-label](https://github.com/eps1lon/actions-label-merge-conflict)
  * ✅ Deployment notification to email
  * ✅ Deployment notification to GitHub
  * ✅ Deployment notification to Jira
  * ✅ Deployment notification to New Relic
  * ✅ Automated dependencies updates ([Renovate](https://www.mend.io/renovate/))
* 📖 Documentation
  * ✅ Your project [README.md](README.md)
  * ✅ Your [project documentation](docs)
  * ✅ [DrevOps documentation](https://docs.drevops.com/)
* 🧪 DrevOps
  * ✅ Unit test coverage for scripts
  * ✅ Functional test coverage for workflows
  * ✅ Integration test coverage for deployments
  * ✅ DrevOps updates
  * ✅ [Basic installer](https://install.drevops.com/)
  * 💡 Advanced installer CLI UI
  * 💡 Advances installer Web UI
  * 💡 Automated project setup
  * 💡 Project dashboard

<br/>

</td></tr>
<tr><td>

**Below is a content of the <code>README.md</code> file that will be added to your project.**

**This table will be removed during installation.**

</td></tr></table>
<br/>

[//]: # (#;> DREVOPS_DEV)
# YOURSITE
Drupal 10 implementation of YOURSITE for YOURORG

[![CircleCI](https://circleci.com/gh/your_org/your_site.svg?style=shield)](https://circleci.com/gh/your_org/your_site)
![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)
[![codecov](https://codecov.io/gh/your_org/your_site/graph/badge.svg)](https://codecov.io/gh/your_org/your_site)

[//]: # (#;< RENOVATEBOT)

[![RenovateBot](https://img.shields.io/badge/RenovateBot-enabled-brightgreen.svg?logo=renovatebot)](https://renovatebot.com)

[//]: # (#;> RENOVATEBOT)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY DREVOPS TO TRACK INTEGRATION)

[![DrevOps](https://img.shields.io/badge/DrevOps-DREVOPS_VERSION_URLENCODED-blue.svg)](https://github.com/drevops/drevops/tree/DREVOPS_VERSION)

[//]: # (Remove the section below once onboarding is finished)
## Onboarding
Use [Onboarding checklist](docs/onboarding.md) to track the project onboarding progress.

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
  2. Copy `.env.local.default` to `.env.local`.
  3. Populate `$DREVOPS_ACQUIA_KEY` and `$DREVOPS_ACQUIA_SECRET` environment
     variables in `.env.local` file with values generated in the step above.

[//]: # (#;> ACQUIA)

[//]: # (#;< LAGOON)

- Authenticate with Lagoon
  1. Create an SSH key and add it to your account in the [Lagoon Dashboard](https://ui-lagoon-master.ch.amazee.io/).
  2. Copy `.env.local.default` to `.env.local`.
  3. Update `$DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE` environment variable in `.env.local` file
     with the path to the SSH key.

[//]: # (#;> LAGOON)


[//]: # (#;< !PROVISION_USE_PROFILE)

- `ahoy download-db`

[//]: # (#;> !PROVISION_USE_PROFILE)
- `pygmy up`
- `ahoy build`

### Apple M1 adjustments

Copy `docker-compose.override.default.yml` to `docker-compose.override.yml`.

## Testing
Please refer to [testing documentation](docs/testing.md).

## CI
Please refer to [CI documentation](docs/ci.md).

[//]: # (#;< DEPLOYMENT)

## Deployment
Please refer to [deployment documentation](docs/deployment.md).

[//]: # (#;> DEPLOYMENT)

## Releasing
Please refer to [releasing documentation](docs/releasing.md).

## FAQs
Please refer to [FAQs](docs/faqs.md).
