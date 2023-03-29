[//]: # (#;< DREVOPS_DEV)
<table align="center"><tr><td>
<p align="center">
	<img width="400" src="https://user-images.githubusercontent.com/378794/228488082-814a8ddc-e749-4031-874c-ef545ac00cec.png" alt="DrevOps Logo" />
</div>
<h3 align="center">Build, Test, Deploy scripts for Drupal using Docker and CI/CD</h3>
<div align="center">

[![CircleCI](https://circleci.com/gh/drevops/drevops.svg?style=shield)](https://circleci.com/gh/drevops/drevops)
![GitHub release (latest by date)](https://img.shields.io/github/v/release/drevops/drevops)
![Drupal 9](https://img.shields.io/badge/Drupal-9-blue.svg)
![LICENSE](https://img.shields.io/github/license/drevops/drevops)
[![Tweet](https://img.shields.io/twitter/url/http/shields.io.svg?style=social)](https://twitter.com/intent/tweet?text=%F0%9F%92%A7%2B%20%F0%9F%90%B3%20%2B%20%E2%9C%93%E2%9C%93%E2%9C%93%20%2B%20%F0%9F%A4%96%20%3D%20DrevOps%20-%20%20Build%2C%20Test%2C%20Deploy%20scripts%20for%20Drupal%20using%20Docker%20and%20CI%2FCD&amp;url=https://www.drevops.com&amp;via=drev_ops&amp;hashtags=drupal,devops,workflow,composer,template,kickstart,ci,test,build)

</div>

</td></tr><tr><td>

## Purpose

Make it easy to create high-quality Drupal websites

## Approach

Use **tested** Drupal project template with DevOps integrations for CI and hosting platforms.

## How it works

1. You run the installer script once.
2. DrevOps integrates the latest project template release into your codebase.
3. You choose which changes to commit.

</td></tr><tr><td>

## Installation

    curl -SsL https://install.drevops.com | php

## Documentation

https://docs.drevops.com

<br/>

</td></tr>
<tr><td>

**Below is a content of the <code>README.md</code> file that will be added to your project.**

**This table will be removed during installation.**

</td></tr></table>
<br/>

[//]: # (#;> DREVOPS_DEV)
# YOURSITE
Drupal 9 implementation of YOURSITE for YOURORG

[![CircleCI](https://circleci.com/gh/your_org/your_site.svg?style=shield)](https://circleci.com/gh/your_org/your_site)
![Drupal 9](https://img.shields.io/badge/Drupal-9-blue.svg)

[//]: # (#;< RENOVATEBOT)

[![RenovateBot](https://img.shields.io/badge/RenovateBot-enabled-brightgreen.svg?logo=renovatebot)](https://renovatebot.com)

[//]: # (#;> RENOVATEBOT)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY DREVOPS TO TRACK INTEGRATION)

[![DrevOps](https://img.shields.io/badge/DrevOps-DREVOPS_VERSION_URLENCODED-blue.svg)](https://github.com/drevops/drevops/tree/DREVOPS_VERSION)

[//]: # (Remove the section below once onboarding is finished)
## Onboarding
Use [Onboarding checklist](docs/ONBOARDING.md) to track the project onboarding progress.

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
  2. Copy `.env.local.example` to `.env.local`.
  3. Populate `$DREVOPS_ACQUIA_KEY` and `$DREVOPS_ACQUIA_SECRET` environment
     variables in `.env.local` file with values generated in the step above.

[//]: # (#;> ACQUIA)

[//]: # (#;< LAGOON)

- Authenticate with Lagoon
  1. Create an SSH key and add it to your account in the [Lagoon Dashboard](https://ui-lagoon-master.ch.amazee.io/).
  2. Copy `.env.local.example` to `.env.local`.
  3. Update `$DREVOPS_DB_DOWNLOAD_SSH_KEY_FILE` environment variable in `.env.local` file
  with the path to the SSH key.

[//]: # (#;> LAGOON)


[//]: # (#;< !INSTALL_FROM_PROFILE)

- `ahoy download-db`

[//]: # (#;> !INSTALL_FROM_PROFILE)
- `pygmy up`
- `ahoy build`

### Apple M1 adjustments

Copy `docker-compose.override.example.yml` to `docker-compose.override.yml`.

## Testing
Please refer to [testing documentation](docs/TESTING.md).

## CI
Please refer to [CI documentation](docs/CI.md).

[//]: # (#;< DEPLOYMENT)

## Deployment
Please refer to [deployment documentation](docs/DEPLOYMENT.md).

[//]: # (#;> DEPLOYMENT)

## Releasing
Please refer to [releasing documentation](docs/RELEASING.md).

## FAQs
Please refer to [FAQs](docs/FAQs.md).
