<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

class TestCommands extends \BluesparkLabs\Spark\Robo\Commands {

  public function testInit() {
    $this->validateConfig();
    $this->title('Initiating tests');
    foreach ($this->config->get('test.init') as $command) {
      $this->taskSparkExec('containers:exec', ['php', $command]);
    }
  }

  public function testExecute() {
    $this->validateConfig();
    $this->title('Running tests');
    foreach ($this->config->get('test.exec') as $command) {
      $this->taskSparkExec('containers:exec', ['php', $command]);
    }
  }

}
