# star wars
Drupal 11 implementation of star wars for star wars Org

<div align="center">

[![CircleCI](https://circleci.com/gh/star_wars_org/star_wars.svg?style=shield)](https://circleci.com/gh/star_wars_org/star_wars)

![Drupal 11](https://img.shields.io/badge/Drupal-10-blue.svg)
[![codecov](https://codecov.io/gh/star_wars_org/star_wars/graph/badge.svg)](https://codecov.io/gh/star_wars_org/star_wars)

![Automated updates](https://img.shields.io/badge/Automated%20updates-RenovateBot-brightgreen.svg)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY VORTEX TO TRACK INTEGRATION)

[![Vortex](https://img.shields.io/badge/Vortex-develop-5909A1.svg)](https://github.com/drevops/vortex/tree/develop)

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

- [Deployment](docs/deployment.md)

---
_This repository was created using the [Vortex](https://github.com/drevops/vortex) project template_
