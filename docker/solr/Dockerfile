FROM wodby/drupal-solr:8-6.6-2.4.0

USER root

RUN mkdir -p /opt/spark-project
ARG SOLR_CONFIG_VER
RUN echo "Using SearchAPI Solr config version: $SOLR_CONFIG_VER"

# Override solr config files with customization of SearchAPI Solr version that
# cannot be customized in the parent image Dockerfile, this improved version
# remove any previous config before move from module config directory.
RUN set -ex; \
    \
    # Downloading config set from search_api_solr drupal module
    url="https://ftp.drupal.org/files/projects/search_api_solr-${SOLR_CONFIG_VER}.tar.gz"; \
    wget -qO- "${url}" | tar xz -C /opt/docker-solr/scripts/; \
    mkdir -p /opt/docker-solr/configsets/drupal; \
    rm -rf /opt/docker-solr/configsets/drupal/conf/; \
    mv "/opt/docker-solr/scripts/search_api_solr/solr-conf/${SOLR_VER:0:1}.x" /opt/docker-solr/configsets/drupal/conf; \
    rm -rf /opt/docker-solr/scripts/search_api_solr; \
    chown -R $SOLR_USER:$SOLR_USER /opt/docker-solr/configsets/drupal/; \
    \
    # Change default config set to drupal
    sed -i -e 's/data_driven_schema_configs/drupal/g' /usr/local/bin/actions.mk; \
    sed -i -e 's/_default/drupal/g' /usr/local/bin/actions.mk

USER $SOLR_USER
