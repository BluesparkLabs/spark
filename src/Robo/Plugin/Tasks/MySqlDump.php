<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Tasks;

use Ifsnop\Mysqldump as IMySqlDump;
use Robo\Task\BaseTask;
use Robo\Result;

class MySqlDump extends BaseTask {

  protected $host = 'localhost';
  protected $port = 3306;
  protected $dbname;
  protected $user = '';
  protected $password = '';

  public function host($host) {
    $this->host = $host;
    return $this;
  }

  public function port($port) {
    $this->port = $port;
    return $this;
  }

  public function dbname($dbname) {
    $this->dbname = $dbname;
    return $this;
  }

  public function user($user) {
    $this->user = $user;
    return $this;
  }

  public function password($password) {
    $this->password = $password;
    return $this;
  }

  public function run() {
    $this->startTimer();
    if (!$this->validateMySqlConnection()) {
      return Result::error($this, 'Failed to connect to database.');
    }
    $this->printTaskInfo('Dumping database from {user}{host}:{port}/{dbname} (using password: {password})', [
      'user' => $this->user ? ($this->user . '@') : '',
      'host' => $this->host,
      'port' => $this->port,
      'dbname' => $this->dbname,
      'password' => $this->password ? 'yes' : 'no',
    ]);
    try {
      $dump = new IMysqldump\Mysqldump($this->getDsn(), $this->user, $this->password);
      $dump->start('dump.sql');
      $this->stopTimer();
      $message = 'Created file dump.sql';
      $this->printTaskSuccess($message);
      $result = Result::success($this, $message, ['time' => $this->getExecutionTime()]);
    }
    catch (\Exception $e) {
      $this->printTaskError($e->getMessage());
      $result = Result::error($this, $e->getMessage());
    }
    return $result;
  }

  protected function validateMySqlConnection() {
    try {
      $dbh = new \PDO($this->getDsn(), $this->user, $this->password);
      $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      return true;
    }
    catch (\PDOException $e){
      $this->printTaskError('Connection to MySQL failed: ' . $e->getMessage());
      return false;
    }
  }

  protected function getDsn() {
    return sprintf('mysql:host=%s;port=%d;dbname=%s',
      $this->host,
      $this->port,
      $this->dbname
    );
  }

}
