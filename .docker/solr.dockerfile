# Solr container.
ARG CLI_IMAGE
# hadolint ignore=DL3006
FROM ${CLI_IMAGE} as cli

# @see https://hub.docker.com/r/uselagoon/solr-8/tags
# @see https://github.com/uselagoon/lagoon-images/blob/main/images/solr/8.Dockerfile
FROM uselagoon/solr-8:24.7.0

# Solr Jump-start config needs to be manually copied from search_api_solr module
# /app/docroot/modules/contrib/search_api_solr/jump-start/solr8/config-set.
COPY .docker/config/solr /solr-conf/conf/

USER root

RUN sed -i -e "s#<dataDir>\${solr.data.dir:}#<dataDir>/var/solr/\${solr.core.name}#g" /solr-conf/conf/solrconfig.xml \
    && sed -i -e "s#solr.lock.type:native#solr.lock.type:none#g" /solr-conf/conf/solrconfig.xml \
    && sed -i -e "s#solr.autoSoftCommit.MaxTime=5000#solr.autoSoftCommit.MaxTime=-1#g" /solr-conf/conf/solrcore.properties

USER solr
# Solr-precreate is provided by the base solr container image and is responsible
# for precreating the core and then starting solr in the foreground.
CMD ["solr-precreate", "drupal", "/solr-conf"]
