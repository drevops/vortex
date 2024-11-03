# PHP FPM container.
#
# All web requests are sent from Nginx to this container.
# This container would be scaled up/down in production.
#
# hadolint global ignore=DL3018
ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE:-cli} as cli

# @see https://hub.docker.com/r/uselagoon/php-8.3-fpm/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-fpm
FROM uselagoon/php-8.3-fpm:24.10.0

RUN apk add --no-cache tzdata

COPY --from=cli /app /app
