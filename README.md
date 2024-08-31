<div align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset=".vortex/docs/static/img/logo-vortex-light.svg" />
    <img width="200" src=".vortex/docs/static/img/logo-vortex-dark.svg" alt="Vortex Logo" />
  </picture>
</div>

<h3 align="center">
  <big>Vortex</big><br/><small>Drupal project template</small>
</h3>

<div align="center">

[![Test](https://github.com/drevops/scaffold/actions/workflows/vortex-test-common.yml/badge.svg)](https://github.com/drevops/scaffold/actions/workflows/vortex-test-common.yml)
[![Test docs](https://github.com/drevops/scaffold/actions/workflows/vortex-test-docs.yml/badge.svg)](https://github.com/drevops/scaffold/actions/workflows/vortex-test-docs.yml)
[![CircleCI](https://circleci.com/gh/drevops/scaffold.svg?style=shield)](https://circleci.com/gh/drevops/scaffold)
[![codecov](https://codecov.io/gh/drevops/scaffold/graph/badge.svg?token=YDTAEWWT5H)](https://codecov.io/gh/drevops/scaffold)
![GitHub release](https://img.shields.io/github/v/release/drevops/scaffold?logo=github)
![LICENSE](https://img.shields.io/github/license/drevops/scaffold)

</div>

Welcome to <strong>Vortex</strong> &mdash; a project template for Drupal designed to simplify onboarding and website maintenance.

At [DrevOps&reg;](https://www.drevops.com/), we carefully maintain this
template, keeping it aligned with the latest tools and validating it through
automated tests to ensure everything works together seamlessly.

Our goal is to provide a consistent developer experience across projects, making
it easier to switch between them and get up to speed quickly.

Track our current progress and view planned updates on [the GitHub project board](https://github.com/orgs/drevops/projects/2/views/1).

## Installation

Our [installer](https://github.com/drevops/vortex-installer) simplifies setup, letting you choose only the features you need. It will integrate the latest scaffold release into your codebase and you will choose which changes to commit.

```bash
curl -SsL https://install.drevops.com > install.php
php install.php
rm -r install.php
```

Alternatively, clone this repository and adjust the configuration by manually
editing or deleting the sections that aren't necessary for your setup.

Read
the [installation guide](https://docs.drevops.com/getting-started/installation)
for more details.

## Features

<div align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset=".vortex/docs/static/img/diagram-dark.png">
    <img src=".vortex/docs/static/img/diagram-light.png" alt="DrevOps diagram">
  </picture>
</div>

The following list includes ✅ completed and 🚧 upcoming features.

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
  * 🚧 [Pantheon](https://pantheon.io/)
  * 🚧 [Platform.sh](https://platform.sh/)
* 💻 Local development
  * ✅ [Docker Compose](https://docs.docker.com/compose/) + [Ahoy](https://github.com/ahoy-cli/ahoy)
  * 🚧 [Lando](https://lando.dev/)
  * 🚧 [DDEV](https://ddev.readthedocs.io/)
* 🏗️ CI/CD
  * ✅ [Circle CI](https://circleci.com/)
  * 🚧 [GitHub Actions](https://github.com/features/actions)
  * 🚧 [GitLab CI](https://docs.gitlab.com/ee/ci/)
  * 🚧 [Azure Pipelines](https://azure.microsoft.com/en-us/services/devops/pipelines/)
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
  * ✅ [Twig-CS-Fixer](https://github.com/VincentLanglet/Twig-CS-Fixer)
  * 🚧 [Pa11y](https://pa11y.org/)
* ⚙️ Workflow
  * ✅ Database from FTP, CURL, container image, hosting provider
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
  * ✅ Your project [README.md](README.dist.md)
  * ✅ Your [project documentation](docs)
  * ✅ [Vortex documentation](https://docs.drevops.com/)
* 🧪 DrevOps
  * ✅ Unit test coverage for scripts
  * ✅ Functional test coverage for workflows
  * ✅ Integration test coverage for deployments
  * ✅ Vortex updates
  * ✅ [Basic installer](https://install.drevops.com/)
  * 🚧 Advanced installer CLI UI
  * 🚧 Advances installer Web UI
  * 🚧 Automated project setup
  * 🚧 Project dashboard

## Documentation

The documentation is authored within this repository in the `.vortex/docs` directory.

It is published to [https://docs.drevops.com](https://docs.drevops.com) on Vortex release.

Development version of the documentation is available at [https://vortex-docs.netlify.app/](https://vortex-docs.netlify.app/).

## Support

We provide paid support for **Vortex**:

- New and existing project onboarding
- Support plans with SLAs
- Priority feature implementation
- Updates to the latest version of the platform
- DevOps consulting and custom implementations

Contact us at support@drevops.com
