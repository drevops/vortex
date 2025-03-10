# sut
Drupal 11 implementation of sut for sut Org

<div align="center">

[![Database, Build, Test and Deploy](https://github.com/sut_org/sut/actions/workflows/build-test-deploy.yml/badge.svg)](https://github.com/sut_org/sut/actions/workflows/build-test-deploy.yml)

![Drupal 11](https://img.shields.io/badge/Drupal-10-blue.svg)
[![codecov](https://codecov.io/gh/sut_org/sut/graph/badge.svg)](https://codecov.io/gh/sut_org/sut)

![Automated updates](https://img.shields.io/badge/Automated%20updates-RenovateBot-brightgreen.svg)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY VORTEX TO TRACK INTEGRATION)

[![Vortex](https://img.shields.io/badge/Vortex--5909A1.svg)](https://github.com/drevops/vortex/tree/stable)

</div>

## Onboarding to Vortex

Use [Onboarding checklist](docs/onboarding.md) to track the project onboarding
to Vortex progress. Remove this section once onboarding is finished.

## Local environment setup

- Make sure that you have latest versions of all required software installed: [Docker](https://www.docker.com/), [Pygmy](https://github.com/pygmystack/pygmy), [Ahoy](https://github.com/ahoy-cli/ahoy)
- Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
- Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/docker-for-mac/osxfs/#access-control)).

- `ahoy download-db`

- `pygmy up`
- `ahoy build`

## Project documentation

- [FAQs](docs/faqs.md)
- [Testing](docs/testing.md)

- [CI](docs/ci.md)

- [Releasing](docs/releasing.md)
- [Deployment](docs/deployment.md)

---
_This repository was created using the [Vortex](https://github.com/drevops/vortex) project template_
