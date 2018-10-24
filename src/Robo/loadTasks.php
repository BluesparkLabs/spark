<?php

namespace BluesparkLabs\Spark\Robo;

use BluesparkLabs\Spark\Robo\Plugin\Tasks\MySqlDump;

trait loadTasks {

  protected function taskMySqlDump() {
    return $this->task(MySqlDump::class);
  }
}
