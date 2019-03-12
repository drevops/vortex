# @see https://github.com/amazeeio/lagoon/tree/master/images/php/fpm
ARG CLI_IMAGE
FROM ${CLI_IMAGE:-cli} as cli

FROM amazeeio/php:7.2-fpm-v0.21.2

COPY --from=cli /app /app
