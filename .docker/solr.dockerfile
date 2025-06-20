# Solr container.
#
# hadolint global ignore=DL3018
#
# @see https://hub.docker.com/r/uselagoon/solr-8/tags
# @see https://github.com/uselagoon/lagoon-images/blob/main/images/solr/8.Dockerfile

ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE:-cli} AS cli

FROM uselagoon/solr-8:25.6.0

# Solr jump-start config needs to be manually copied from the search_api_solr
# Drupal module to .docker/config/solr/config-set.
COPY .docker/config/solr/config-set /solr-conf/conf/

USER root

RUN sed -i -e "s#<dataDir>\${solr.data.dir:}#<dataDir>/var/solr/\${solr.core.name}#g" /solr-conf/conf/solrconfig.xml \
    && sed -i -e "s#solr.lock.type:native#solr.lock.type:none#g" /solr-conf/conf/solrconfig.xml \
    && sed -i -e "s#solr.autoSoftCommit.MaxTime=5000#solr.autoSoftCommit.MaxTime=-1#g" /solr-conf/conf/solrcore.properties

USER solr

# solr-precreate is provided by the base Solr container image.
# It pre-creates the core and then starts Solr in the foreground.
CMD ["solr-precreate", "drupal", "/solr-conf"]
