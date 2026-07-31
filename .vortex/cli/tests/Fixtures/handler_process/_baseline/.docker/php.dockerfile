# PHP FPM container.
#
# All web requests are sent from Nginx to this container.
# This container would be scaled up/down in production.
#
# @see https://hub.docker.com/r/uselagoon/php-8.4-fpm/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-fpm

ARG CLI_IMAGE
FROM ${CLI_IMAGE:-cli} AS cli

FROM uselagoon/php-8.4-fpm:__VERSION__

# hadolint ignore=DL3018 # the package set tracks the pinned base image
RUN apk add --no-cache tzdata

COPY --from=cli /app /app
