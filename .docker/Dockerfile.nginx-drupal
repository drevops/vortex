# @see https://github.com/amazeeio/lagoon/tree/master/images/nginx-drupal
ARG CLI_IMAGE
FROM ${CLI_IMAGE:-cli} as cli

FROM amazeeio/nginx-drupal:v0.22.1

ENV WEBROOT=docroot

COPY --from=cli /app /app
