<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

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

  public function mysqlDump($opts = ['non-sanitized' => false]) {
    $this->validateConfig();
    $task = $this->taskMySqlDump()
      ->host($this->database['host'])
      ->port($this->database['port'])
      ->dbname($this->database['dbname'])
      ->user($this->database['user'])
      ->password($this->database['password']);
    if (!$opts['non-sanitized'] && $this->config->has('database-sanitization')) {
      $task->sanitize($this->config->get('database-sanitization.rules'));
      if ($this->config->has('database-sanitization.faker-locale')) {
        $task->fakerLocale($this->config->get('database-sanitization.faker-locale'));
      }
    }
    $task->run();
  }

}
