# check=skip=SecretsUsedInArgOrEnv
# Database container.
#
# The `MYSQL_PASSWORD` variable below is flagged by name by the
# `SecretsUsedInArgOrEnv` build check. This is a local development database
# with a non-secret default credential, so that check is skipped.
#
# @see https://hub.docker.com/r/uselagoon/mysql-8.4/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/mysql
#
# The ARG value will be updated with a value passed from docker-compose.yml

ARG IMAGE=uselagoon/mysql-8.4:26.7.0
# hadolint ignore=DL3006
FROM ${IMAGE}

USER root
COPY ./.docker/config/database/my.cnf /etc/my.cnf.d/server.cnf
RUN fix-permissions /etc/my.cnf.d/

ENV MYSQL_DATABASE=drupal \
    MYSQL_USER=drupal \
    MYSQL_PASSWORD=drupal

USER mysql
