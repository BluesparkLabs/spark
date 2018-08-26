<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

use Ifsnop\Mysqldump as MySqlDump;

class MySqlCommands extends \BluesparkLabs\Spark\Robo\Commands {

  private $database;

  public function __construct() {
    parent::__construct();
    // Try to use database connection credentials from .spark.local.yml.
    if ($this->config_local && $this->config_local->has('database')) {
      $this->database = $this->config_local->get('database');
    }
    // Fall back to values that work with the MySQL container Spark provides.
    else {
      $this->database = [
        'host' => '127.0.0.1',
        'port' => '7501',
        'dbname' => 'spark',
        'user' => 'spark',
        'password' => 'spark',
      ];
    }
  }

  public function mysqlDump() {
    $this->validateConfig();
    $this->validateMySqlConnection();
    try {
      $dump = new MySqlDump\Mysqldump($this->getDsn(), $this->database['user'], $this->database['password']);
      $dump->start('dump.sql');
    }
    catch (\Exception $e) {
      $this->io()->error($e->getMessage());
    }
  }

  private function validateMySqlConnection() {
    try {
      $dbh = new \PDO($this->getDsn(), $this->database['user'], $this->database['password']);
      $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
    catch (\PDOException $e){
      $this->io()->error('Connection to MySQL failed: ' . $e->getMessage());
      $notes = [];
      if ($this->config_local && $this->config_local->has('database')) {
        $notes[] = 'Check the database connection details you provided in your .spark.local.yml file.';
      }
      else {
        $notes[] = 'Spark attempted to use its own MySQL container. Make sure you start it with the `containers:start` command.';
        $notes[] = 'If you wish to connect to a different database, please provide database connection details in your .spark.local.yml file.';
      }
      $notes[] = '📖  Documentation: https://github.com/BluesparkLabs/spark';
      $this->io()->note($notes);
      die(1);
    }
  }

  private function getDsn() {
    return sprintf('mysql:host=%s;port=%d;dbname=%s',
      $this->database['host'],
      $this->database['port'],
      $this->database['dbname']
    );
  }

}
