# Database container.
#
# @see https://hub.docker.com/r/uselagoon/mysql-8.4/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/mysql
#
# The ARG value will be updated with a value passed from docker-compose.yml

ARG IMAGE=uselagoon/mysql-8.4:26.8.0
FROM ${IMAGE}

# hadolint ignore=DL3066 # named account provided by the base image
USER root
COPY ./.docker/config/database/my.cnf /etc/my.cnf.d/server.cnf
RUN fix-permissions /etc/my.cnf.d/

# hadolint ignore=DL3064 # local development credentials only
ENV MYSQL_DATABASE=drupal \
    MYSQL_USER=drupal \
    MYSQL_PASSWORD=drupal

# hadolint ignore=DL3066 # named account provided by the base image
USER mysql
