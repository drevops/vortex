# Nginx container.
#
# All web requests are sent to this container.
#
# hadolint global ignore=DL3018
#
# @see https://hub.docker.com/r/uselagoon/nginx-drupal/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/nginx-drupal

ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE:-cli} AS cli

FROM uselagoon/nginx-drupal:25.8.0

# Webroot is used for Nginx web root configuration.
ARG WEBROOT=web
ENV WEBROOT=${WEBROOT}

RUN apk add --no-cache tzdata

COPY ./.docker/config/nginx/redirects-map.conf /etc/nginx/redirects-map.conf

COPY --from=cli /app /app
