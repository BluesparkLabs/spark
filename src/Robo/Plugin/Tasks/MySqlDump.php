<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Tasks;

use Cocur\Slugify\Slugify;
use Faker\Factory as FakerFactory;
use Ifsnop\Mysqldump as IMySqlDump;
use Robo\Task\BaseTask;
use Robo\Result;
use Webmozart\PathUtil\Path;

class MySqlDump extends BaseTask {

  protected $host = 'localhost';
  protected $port = 3306;
  protected $dbname;
  protected $user = '';
  protected $password = '';
  protected $sanitizations = [];
  protected $fakerLocale = 'en_US';
  protected $faker;
  protected $projectName = '';
  protected $environmentName = '';
  protected $destination = '';
  protected $excludeTables = [];
  protected $noData = [];

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

  public function sanitize($sanitizations) {
    $this->sanitizations = $sanitizations;
    return $this;
  }

  public function fakerLocale($fakerLocale) {
    $this->fakerLocale = $fakerLocale;
    return $this;
  }

  public function projectName($projectName) {
    $this->projectName = $projectName;
  }

  public function environmentName($environmentName) {
    $this->environmentName = $environmentName;
  }

  public function destination($destination) {
    $this->destination = $destination;
  }

  public function excludeTables($excludeTables) {
    $this->excludeTables = $excludeTables;
  }

  public function noData($noData) {
    $this->noData = $noData;
  }

  public function run() {
    $this->startTimer();
    if (!$this->validateMySqlConnection()) {
      return Result::error($this, 'Failed to connect to database.');
    }
    $this->printTaskInfo('Dumping database from {user}{host}:{port}/{dbname} (using password: {password})', [
      'user' => $this->user ? ($this->user . '@') : '',
      'host' => $this->host,
      'port' => (string) $this->port,
      'dbname' => $this->dbname,
      'password' => $this->password ? 'yes' : 'no',
    ]);
    try {
      $dump = new IMysqldump\Mysqldump($this->getDsn(), $this->user, $this->password, [
        'compress' => IMysqldump\Mysqldump::GZIP,
        'exclude-tables' => $this->excludeTables,
        'no-data' => $this->noData,
      ]);
      if (!empty($this->sanitizations)) {
        $this->printTaskInfo('Sanitizing values using Faker ({locale})', ['locale' => $this->fakerLocale]);
        $this->faker = FakerFactory::create($this->fakerLocale);
        $dump->setTransformColumnValueHook(function($tableName, $colName, $colValue) {
          return $this->sanitizeValues($tableName, $colName, $colValue);
        });
      }
      else {
        $this->printTaskWarning('⚠️  Skipping data sanitization.');
      }
      if ($this->excludeTables) {
        $this->printTaskInfo('Excluding tables {tables}', ['tables' => implode(', ', $this->excludeTables)]);
      }
      if ($this->noData) {
        $this->printTaskInfo('Discarding data from {tables}', ['tables' => implode(', ', $this->noData)]);
      }
      $filename = $this->filename();
      $destinationDirectory = $this->destinationDirectory();
      $this->printTaskInfo('Destination directory: {destination}', ['destination' => $destinationDirectory]);
      $dump->start($destinationDirectory . '/' . $filename);
      $this->stopTimer();
      $message = 'Created file: {filename}';
      $this->printTaskSuccess($message, ['filename' => $filename]);
      $result = Result::success($this, $message, ['time' => $this->getExecutionTime(), 'filename' => $filename]);
    }
    catch (\Exception $e) {
      $result = Result::error($this, $e->getMessage());
    }
    return $result;
  }

  protected function filename() {
    $slugify = new Slugify();
    return sprintf('%s%s--db--%s--%s.sql.gz',
      $slugify->slugify($this->projectName),
      $this->environmentName ? ('--' . $slugify->slugify($this->environmentName)) : '',
      !empty($this->sanitizations) ? 'sanitized' : 'NON-SANITIZED',
      // Date: UTC, ISO 8601 standard.
      gmdate('Y-m-d\THi\Z', time())
    );
  }

  protected function destinationDirectory() {
    if (!$this->destination) {
      return getenv('SPARK_WORKDIR');
    }
    $destination = rtrim($this->destination);
    if (strpos($this->destination, '/') === 0) {
      return $destination;
    }
    return Path::canonicalize(getenv('SPARK_WORKDIR') . '/' . $destination);
  }

  protected function sanitizeValues($tableName, $colName, $colValue) {
    if (isset($this->sanitizations[$tableName][$colName]) && !empty($colValue)) {
      try {
        return $this->faker->{$this->sanitizations[$tableName][$colName]};
      }
      catch (\InvalidArgumentException $e) {
        throw new \Exception(sprintf('The formatter "%s" which you attempted to use to sanitize the value of the column "%s" in the table "%s" is unknown to Faker. Please fix it in your .spark.yml file. See the list of available formatters here: https://github.com/fzaninotto/Faker#formatters.', $this->sanitizations[$tableName][$colName], $tableName, $colName));
      }
    }
    return $colValue;
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
