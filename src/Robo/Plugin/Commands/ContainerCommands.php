<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

class ContainerCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  use \Droath\RoboDockerCompose\Task\loadTasks;

  public function __construct() {
    parent::__construct();
  }

  public function containersStart() {
    $this->validateConfig();
    $this->title('Starting containers');
    $this->taskDockerComposeUp()
      ->file($this->dockerComposeFile)
      ->projectName($this->config->get('name'))
      ->detachedMode()
      ->run();
  }

  public function containersDestroy() {
    $this->validateConfig();
    $this->title('Destroying containers');
    $this->taskDockerComposeDown()
      ->file($this->dockerComposeFile)
      ->projectName($this->config->get('name'))
      ->run();
  }

  public function containersExec($container, $execute_command) {
    $this->validateConfig();
    $this->title('Executing on container: ' . $container, FALSE);
    $this->taskDockerComposeExecute()
      ->file($this->dockerComposeFile)
      ->projectName($this->config->get('name'))
      ->disablePseudoTty()
      ->setContainer(' ' . $container)
      ->exec($execute_command)
      ->run();
  }

  public function containersSsh() {
    $this->title('Logging in to PHP container');
    $this->say('This command is not implemented yet. Copy and execute the following:');
    $this->io()->text('docker-compose --file ./vendor/bluesparklabs/spark/docker/docker-compose.d8.yml --project-name \'Spark Example\' exec php bash');
  }
}
