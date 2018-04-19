<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

class SolrCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  public function solrInit() {
    $this->taskSparkExec('containers:exec', ['solr', 'make init -f /usr/local/bin/actions.mk']);
  }

}
