# Vortex installer

## Maintenance

    composer install
    composer lint
    composer test

### Releasing

The installer is packaged as a PHAR and deployed to https://vortex.drevops.com/install
upon each GitHub release or for every branch to a branch containing the
`release-docs` or `release-installer` in the name.
