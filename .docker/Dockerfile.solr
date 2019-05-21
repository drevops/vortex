# @see https://github.com/amazeeio/lagoon/tree/master/images/solr-drupal
ARG CLI_IMAGE
FROM ${CLI_IMAGE} as cli

FROM amazeeio/solr:6.6-drupal-v0.22.1
# Uncomment below after installing search_api_solr module (it must exist in the codebase).
# COPY --from=cli /app/docroot/modules/contrib/search_api_solr/solr-conf/6.x/ /solr-conf/conf/
