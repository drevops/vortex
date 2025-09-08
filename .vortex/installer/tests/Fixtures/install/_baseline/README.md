<div align="center">

# star wars

Drupal 11 implementation of star wars for star wars Org

[![Database, Build, Test and Deploy](https://github.com/star_wars_org/star_wars/actions/workflows/build-test-deploy.yml/badge.svg)](https://github.com/star_wars_org/star_wars/actions/workflows/build-test-deploy.yml)

![Drupal 11](https://img.shields.io/badge/Drupal-11-blue.svg)
[![codecov](https://codecov.io/gh/star_wars_org/star_wars/graph/badge.svg)](https://codecov.io/gh/star_wars_org/star_wars)

![Automated updates](https://img.shields.io/badge/Automated%20updates-RenovateBot-brightgreen.svg)

[//]: # (DO NOT REMOVE THE BADGE BELOW. IT IS USED BY VORTEX TO TRACK INTEGRATION)

[![Vortex](https://img.shields.io/badge/Vortex-develop-65ACBC.svg)](https://github.com/drevops/vortex/tree/develop)

</div>

## Environments

- DEV: https://dev.star-wars.com
- STAGE: https://stage.star-wars.com
- PROD: https://www.star-wars.com

## Local environment setup

- Make sure that you have latest versions of all required software installed: [Docker](https://www.docker.com/), [Pygmy](https://github.com/pygmystack/pygmy), [Ahoy](https://github.com/ahoy-cli/ahoy)
- Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
- Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/desktop/settings-and-maintenance/settings/#virtual-file-shares)).

- `ahoy download-db`

- `pygmy up`
- `ahoy build`

## Project documentation

- [FAQs](docs/faqs.md)
- [Testing](docs/testing.md)

- [CI](docs/ci.md)

- [Deployment](docs/deployment.md)

---
_This repository was created using the [Vortex](https://github.com/drevops/vortex) Drupal project template_
