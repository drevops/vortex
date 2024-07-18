# Nginx container.
#
# All web requests are sent to this container.
#
# hadolint global ignore=DL3018
ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE:-cli} as cli

# @see https://hub.docker.com/r/uselagoon/nginx-drupal/tags?page=1
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/nginx-drupal
FROM uselagoon/nginx-drupal:24.7.0

# Webroot is used for Nginx docroot configuration.
ARG WEBROOT=web
ENV WEBROOT=${WEBROOT}

RUN apk add --no-cache tzdata

COPY --from=cli /app /app
