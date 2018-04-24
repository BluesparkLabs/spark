<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

class DbCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  public function dbDrop() {
    $this->title('Dropping database');
    $this->taskSparkExec('drush', ['sql-drop -y']);
  }

  public function dbImport() {
    $this->title('Importing database dump');
    $this->taskSparkContainerShExec('db', 'mysql -u root --password=root spark < /opt/spark-project/.spark/db-import.sql');
  }

}
