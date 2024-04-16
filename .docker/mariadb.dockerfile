# MariaDB container.
#
# @see https://hub.docker.com/r/uselagoon/mariadb-drupal/tags?page=1
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/mariadb-drupal
#
# Use drevops/drevops-mariadb-drupal-data as a starting Docker image for your
# Database-in-Docker-image database.
# @see https://github.com/drevops/mariadb-drupal-data
#
# The ARG value will be updated with a value passed from docker-compose.yml
ARG IMAGE=uselagoon/mariadb-drupal:24.3.1

# hadolint ignore=DL3006
FROM ${IMAGE}

USER root
COPY ./.docker/config/mariadb/my.cnf /etc/my.cnf.d/server.cnf
RUN fix-permissions /etc/my.cnf.d/

USER mysql
