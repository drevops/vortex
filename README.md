<div align="center">
	<img width="400" src="https://user-images.githubusercontent.com/378794/228488082-814a8ddc-e749-4031-874c-ef545ac00cec.png" alt="DrevOps Logo" />
</div>

<h3 align="center">Drupal project template</h3>
<h4 align="center"><em>Onboarding in minutes, not hours or days!</em></h4>

<div align="center">

[![Test](https://github.com/drevops/drevops/actions/workflows/drevops-test-common.yml/badge.svg)](https://github.com/drevops/drevops/actions/workflows/drevops-test-common.yml)
[![Test docs](https://github.com/drevops/drevops/actions/workflows/drevops-test-docs.yml/badge.svg)](https://github.com/drevops/drevops/actions/workflows/drevops-test-docs.yml)
[![CircleCI](https://circleci.com/gh/drevops/drevops.svg?style=shield)](https://circleci.com/gh/drevops/drevops)
[![codecov](https://codecov.io/gh/drevops/drevops/graph/badge.svg?token=YDTAEWWT5H)](https://codecov.io/gh/drevops/drevops)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/drevops)
![LICENSE](https://img.shields.io/github/license/drevops/drevops)

![Drupal 10](https://img.shields.io/badge/Drupal-10-blue.svg)

[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=%F0%9F%92%A7%2B%20%F0%9F%90%B3%20%2B%20%E2%9C%93%E2%9C%93%E2%9C%93%20%2B%20%F0%9F%A4%96%20%3D%20DrevOps%20-%20%20Build%2C%20Test%2C%20Deploy%20scripts%20for%20Drupal%20using%20Docker%20and%20CI%2FCD&amp;url=https://www.drevops.com&amp;via=drev_ops&amp;hashtags=drupal,devops,workflow,composer,template,kickstart,ci,test,build)

</div>

## Purpose

Make it easy to set up, develop and support high-quality Drupal websites

## Approach

Use **tested** Drupal project template with DevOps integrations for CI and hosting platforms.

## How it works

1. You run the installer script once.
2. DrevOps integrates the latest project template release into your codebase.
3. You choose which changes to commit.

## Installation

```bash
curl -SsL https://install.drevops.com > install.php
php install.php
rm -r install.php
```

## Documentation

https://docs.drevops.com

## Features

<div align="center">
  <img  alt="diagram-shortest" src="https://github.com/drevops/drevops/assets/378794/68e7553b-6c29-437a-8a30-45e9d746180a">
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
  * ✅ Your project [README.md](README.dist.md)
  * ✅ Your [project documentation](docs)
  * ✅ [DrevOps documentation](https://docs.drevops.com/)
* 🧪 DrevOps
  * ✅ Unit test coverage for scripts
  * ✅ Functional test coverage for workflows
  * ✅ Integration test coverage for deployments
  * ✅ DrevOps updates
  * ✅ [Basic installer](https://install.drevops.com/)
  * 🚧 Advanced installer CLI UI
  * 🚧 Advances installer Web UI
  * 🚧 Automated project setup
  * 🚧 Project dashboard
