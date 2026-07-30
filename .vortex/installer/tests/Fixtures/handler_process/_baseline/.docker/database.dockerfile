# Database container.
#
# @see https://hub.docker.com/r/uselagoon/mysql-8.4/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/mysql
#
# The `USER` names below are defined by the base image. Their numeric ids are
# an implementation detail of that image and would break on a base image bump,
# so DL3066 is skipped.
#
# The credentials below are fixed local development values shared with
# `docker-compose.yml`. This container never runs in a deployed environment,
# so DL3064 is skipped.
#
# hadolint global ignore=DL3064,DL3066
#
# The ARG value will be updated with a value passed from docker-compose.yml

ARG IMAGE=uselagoon/mysql-8.4:__VERSION__
# hadolint ignore=DL3006
FROM ${IMAGE}

USER root
COPY ./.docker/config/database/my.cnf /etc/my.cnf.d/server.cnf
RUN fix-permissions /etc/my.cnf.d/

ENV MYSQL_DATABASE=drupal \
    MYSQL_USER=drupal \
    MYSQL_PASSWORD=drupal

USER mysql
