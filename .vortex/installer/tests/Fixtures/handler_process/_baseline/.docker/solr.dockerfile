# Solr container.
#
# @see https://hub.docker.com/r/uselagoon/solr-9-drupal/tags
# @see https://github.com/uselagoon/lagoon-images/blob/main/images/solr-drupal/9.Dockerfile

FROM uselagoon/solr-9-drupal:__VERSION__

# Solr jump-start config needs to be manually copied from the search_api_solr
# Drupal module to .docker/config/solr/config-set.
#
# solr.luceneMatchVersion in solrcore.properties must match the Lucene version
# bundled in this image (see the lucene-core-*.jar filename inside it), which is
# newer than the value shipped in the module's jump-start config-set. Re-check it
# whenever this image tag is bumped.
COPY .docker/config/solr/config-set /solr-conf/conf/

# hadolint ignore=DL3066 # named account provided by the base image
USER root

# Apply custom modifications for Lagoon environment compatibility.
# The index is moved out of the core directory, which is recreated on every
# start, so the build fails if the dataDir replacement does not apply.
RUN sed -i -e "s#<dataDir>\${solr.data.dir:}#<dataDir>/var/solr/\${solr.core.name}#g" /solr-conf/conf/solrconfig.xml && \
    grep -q "<dataDir>/var/solr/\${solr.core.name}</dataDir>" /solr-conf/conf/solrconfig.xml && \
    sed -i -e "s#solr.lock.type:native#solr.lock.type:none#g" /solr-conf/conf/solrconfig.xml && \
    sed -i -e "s#solr.autoSoftCommit.MaxTime=5000#solr.autoSoftCommit.MaxTime=-1#g" /solr-conf/conf/solrcore.properties

# hadolint ignore=DL3066 # named account provided by the base image
USER solr

# solr-recreate and solr-foreground are provided by the base Solr container
# image. solr-recreate replaces the core config on the persistent volume with
# the config-set from this image on every start, so config-set changes reach
# existing cores. exec makes Solr the direct child of tini to receive SIGTERM.
CMD ["sh", "-c", "solr-recreate drupal /solr-conf && exec solr-foreground"]
