# PHP FPM container.
#
# All web requests are sent from Nginx to this container.
# This container would be scaled up/down in production.
ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE:-cli} as cli

# @see https://hub.docker.com/r/uselagoon/php-8.2-fpm/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-fpm
FROM uselagoon/php-8.2-fpm:23.12.0

RUN apk add --no-cache tzdata=2023d-r0

COPY --from=cli /app /app
