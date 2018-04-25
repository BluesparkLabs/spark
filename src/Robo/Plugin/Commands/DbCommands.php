<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

class DbCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  public function dbCheckReady() {
    $this->title('Waiting for MariaDB to start');
    // See https://github.com/wodby/mariadb#orchestration-actions.
    $this->taskSparkContainerExec('db', 'make check-ready max_try=10 wait_seconds=3 -f /usr/local/bin/actions.mk');
  }

  public function dbDrop() {
    $this->title('Dropping database');
    $this->taskSparkExec('drush', ['sql-drop -y']);
  }

  public function dbImport() {
    $this->title('Importing database dump');
    $this->taskSparkContainerExec('db', 'mysql -u root --password=root spark < /opt/spark-project/.spark/db-import.sql');
  }

}
