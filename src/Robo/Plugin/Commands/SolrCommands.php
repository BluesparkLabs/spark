<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

class SolrCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  public function solrInit() {
    $this->title('Creating Solr core');
    $commands = [
      // See https://github.com/wodby/solr#orchestration-actions.
      'make init -f /usr/local/bin/actions.mk',
      'tar xfz /opt/spark-project/.spark/solr-data.tar.gz -C /opt/solr/server/solr/default',
      'make reload core=default -f /usr/local/bin/actions.mk',
    ];
    $this->taskSparkContainerExec('solr', $commands);
  }

}
