# Nginx container.
#
# All web requests are sent to this container.
ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE:-cli} as cli

# @see https://hub.docker.com/r/uselagoon/nginx-drupal/tags?page=1
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/nginx-drupal
FROM uselagoon/nginx-drupal:23.8.0

# Webroot is used for Nginx docroot configuration.
ARG WEBROOT=web
ENV WEBROOT=${WEBROOT}

RUN apk add --no-cache tzdata=2023c-r1

COPY --from=cli /app /app
