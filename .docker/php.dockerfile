# PHP FPM container.
#
# All web requests are sent from Nginx to this container.
# This container would be scaled up/down in production.
ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE:-cli} as cli

# @see https://hub.docker.com/r/uselagoon/php-7.4-fpm/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-fpm
FROM uselagoon/php-8.1-fpm:23.11.0

RUN apk add --no-cache tzdata=2023c-r1

COPY --from=cli /app /app
