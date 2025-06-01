# PHP FPM container.
#
# All web requests are sent from Nginx to this container.
# This container would be scaled up/down in production.
#
# hadolint global ignore=DL3018
#
# @see https://hub.docker.com/r/uselagoon/php-8.3-fpm/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-fpm

ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE:-cli} AS cli

FROM uselagoon/php-8.3-fpm:25.5.0

RUN apk add --no-cache tzdata

COPY --from=cli /app /app
