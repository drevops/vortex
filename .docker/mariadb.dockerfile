# MariaDB container.
#
# @see https://hub.docker.com/r/uselagoon/mariadb-10.11-drupal/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/mariadb-drupal
#
# Use drevops/drevops-mariadb-drupal-data as a starting container image for your
# database-in-image database.
# @see https://github.com/drevops/mariadb-drupal-data
#
# The ARG value will be updated with a value passed from docker-compose.yml
ARG IMAGE=uselagoon/mariadb-10.11-drupal:24.8.0

# hadolint ignore=DL3006
FROM ${IMAGE}

USER root
COPY ./.docker/config/mariadb/my.cnf /etc/my.cnf.d/server.cnf
RUN fix-permissions /etc/my.cnf.d/

USER mysql
