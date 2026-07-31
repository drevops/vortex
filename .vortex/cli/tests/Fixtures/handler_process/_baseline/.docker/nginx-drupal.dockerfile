# Nginx container.
#
# All web requests are sent to this container.
#
# @see https://hub.docker.com/r/uselagoon/nginx-drupal/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/nginx-drupal

ARG CLI_IMAGE
FROM ${CLI_IMAGE:-cli} AS cli

FROM uselagoon/nginx-drupal:__VERSION__

# Webroot is used for Nginx web root configuration.
ARG WEBROOT=web
ENV WEBROOT=${WEBROOT}

# hadolint ignore=DL3018 # the package set tracks the pinned base image
RUN apk add --no-cache tzdata

COPY ./.docker/config/nginx/redirects-map.conf /etc/nginx/redirects-map.conf

RUN fix-permissions /etc/nginx

COPY --from=cli /app /app
