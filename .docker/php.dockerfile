# PHP FPM container.
#
# All web requests are sent from Nginx to this container.
# This container would be scaled up/down in production.
ARG CLI_IMAGE
FROM ${CLI_IMAGE:-cli} as cli

# @see https://hub.docker.com/r/uselagoon/php-7.4-fpm/tags
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/php-fpm
FROM uselagoon/php-8.0-fpm:22.4.1

COPY --from=cli /app /app
