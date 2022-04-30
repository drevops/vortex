# Solr container.
ARG CLI_IMAGE
FROM ${CLI_IMAGE} as cli

# @see https://hub.docker.com/r/uselagoon/solr/tags?page=1&name=drupal
# @see https://github.com/uselagoon/lagoon-images/tree/main/images/solr-drupal
FROM uselagoon/solr-7.7-drupal:22.4.1

# Based off search_api_solr/solr-conf-templates/7.x as a sane default.
COPY .docker/config/solr /solr-conf/conf/

USER root
RUN sed -i -e "s#<dataDir>\${solr.data.dir:}#<dataDir>/var/solr/\${solr.core.name}#g" /solr-conf/conf/solrconfig.xml \
    && sed -i -e "s#solr.lock.type:native#solr.lock.type:none#g" /solr-conf/conf/solrconfig.xml \
    && sed -i -e "s#solr.install.dir=../../..#solr.install.dir=/opt/solr#g" /solr-conf/conf/solrcore.properties

RUN sed -i -e "s#SEARCH_API_SOLR_MIN_SCHEMA_VERSION#4.2.1#g" /solr-conf/conf/schema.xml \
    && sed -i -e "s#SEARCH_API_SOLR_JUMP_START_CONFIG_SET#1#g" /solr-conf/conf/schema.xml \
    && sed -i -e "s#SEARCH_API_SOLR_MIN_SCHEMA_VERSION#4.2.1#g" /solr-conf/conf/solrconfig.xml \
    && sed -i -e "s#SEARCH_API_SOLR_JUMP_START_CONFIG_SET#1#g" /solr-conf/conf/solrconfig.xml \
    && sed -i -e "s#drupal-8.3.8-solr-7.x#drupal-4.2.1-solr-7.x-1#g" /opt/solr/server/solr/mycores/drupal/conf/schema.xml \
    && sed -i -e "s#drupal-8.3.8-solr-7.x#drupal-4.2.1-solr-7.x-1#g" /opt/solr/server/solr/mycores/drupal/conf/solrconfig.xml \
    && sed -i -e "s#drupal-4.1.1-solr-7.x-1#drupal-4.2.1-solr-7.x-1#g" /opt/solr/server/solr/mycores/drupal/conf/schema.xml \
    && sed -i -e "s#drupal-4.1.1-solr-7.x-1#drupal-4.2.1-solr-7.x-1#g" /opt/solr/server/solr/mycores/drupal/conf/solrconfig.xml

USER solr
RUN precreate-core drupal /solr-conf/conf

CMD ["solr-foreground"]
