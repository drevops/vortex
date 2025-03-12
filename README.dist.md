# YOURSITE
Drupal 11 implementation of YOURSITE for YOURORG

<div align="center">

[//]: # (#;< CI_PROVIDER_CIRCLECI)

[![CircleCI](https://circleci.com/gh/your_org/your_site.svg?style=shield)](https://circleci.com/gh/your_org/your_site)

[//]: # (#;> CI_PROVIDER_CIRCLECI)

[//]: # (#;< CI_PROVIDER_GHA)

[![Database, Build, Test and Deploy](https://github.com/your_org/your_site/actions/workflows/build-test-deploy.yml/badge.svg)](https://github.com/your_org/your_site/actions/workflows/build-test-deploy.yml)

[//]: # (#;> CI_PROVIDER_GHA)

![Drupal 11](https://img.shields.io/badge/Drupal-10-blue.svg)
[![codecov](https://codecov.io/gh/your_org/your_site/graph/badge.svg)](https://codecov.io/gh/your_org/your_site)

[//]: # (#;< RENOVATEBOT)

![Automated updates](https://img.shields.io/badge/Automated%20updates-RenovateBot-brightgreen.svg)

[//]: # (#;> RENOVATEBOT)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY VORTEX TO TRACK INTEGRATION)

[![Vortex](https://img.shields.io/badge/Vortex-VORTEX_VERSION_URLENCODED-5909A1.svg)](https://github.com/drevops/vortex/tree/VORTEX_VERSION)

[//]: # (#;< ONBOARDING)

</div>

## Onboarding to Vortex

Use [Onboarding checklist](docs/onboarding.md) to track the project onboarding
to Vortex progress. Remove this section once onboarding is finished.

[//]: # (#;> ONBOARDING)

## Local environment setup

- Make sure that you have latest versions of all required software installed: [Docker](https://www.docker.com/), [Pygmy](https://github.com/pygmystack/pygmy), [Ahoy](https://github.com/ahoy-cli/ahoy)
- Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
- Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/docker-for-mac/osxfs/#access-control)).

[//]: # (#;< ACQUIA)

- Authenticate with Acquia Cloud API
  1. Create your Acquia Cloud API token:<br/>
     Acquia Cloud UI -> Account -> API tokens -> Create Token
  2. Copy `.env.local.example` to `.env.local`.
  3. Populate `$VORTEX_ACQUIA_KEY` and `$VORTEX_ACQUIA_SECRET` environment
     variables in `.env.local` file with values generated in the step above.

[//]: # (#;> ACQUIA)

[//]: # (#;< LAGOON)

- Authenticate with Lagoon
  1. Create an SSH key and add it to your account in the [Lagoon Dashboard](https://ui-lagoon-master.ch.amazee.io/).
  2. Copy `.env.local.example` to `.env.local`.
  3. Update `$VORTEX_DB_DOWNLOAD_SSH_FILE` environment variable in `.env.local` file
     with the path to the SSH key.

[//]: # (#;> LAGOON)


[//]: # (#;< !PROVISION_USE_PROFILE)

- `ahoy download-db`

[//]: # (#;> !PROVISION_USE_PROFILE)
- `pygmy up`
- `ahoy build`

## Project documentation

- [FAQs](docs/faqs.md)
- [Testing](docs/testing.md)

[//]: # (#;< CI_PROVIDER_ANY)

- [CI](docs/ci.md)

[//]: # (#;> CI_PROVIDER_ANY)

- [Deployment](docs/deployment.md)

---
_This repository was created using the [Vortex](https://github.com/drevops/vortex) project template_
