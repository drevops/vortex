# Vortex CLI

## Maintenance

```bash
composer install
composer lint
composer test
```

### Releasing

The CLI is packaged as a PHAR (`vortex.phar`) and published to
https://www.vortextemplate.com/v2/install. When 2.x is the current major,
the same PHAR is also published to https://www.vortextemplate.com/install.

Publishing runs on every tag push and on every push to a branch whose name
contains `release-docs`, `release-cli`, or `release-installer`.
