<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

use Robo\Contract\CommandInterface;
use Robo\Contract\VerbosityThresholdInterface;

class ContainerCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  use \Droath\RoboDockerCompose\Task\loadTasks;

  public function __construct() {
    parent::__construct();
  }

  /**
   * Compose all the docker containers.
   *
   * @param string $container
   *   Container name, when not provided all containers are started.
   */
  public function containersStart($container = NULL) {
    $this->validateConfig();
    $this->title('Starting containers');
    $command = $this->taskDockerComposeUp();

    if ($container) {
      $this->limitComposeContainer($command, $container);
    }

    $command->file($this->dockerComposeFile)
      ->projectName($this->config->get('name'))
      ->detachedMode()
      ->run();
  }

  /**
   * Destroy docker containers.
   */
  public function containersDestroy() {
    $this->validateConfig();
    $this->title('Destroying containers');
    $this->taskDockerComposeDown()
      ->file($this->dockerComposeFile)
      ->projectName($this->config->get('name'))
      ->run();
  }

  /**
   * Execute a command on a given container.
   *
   * @param string $container
   *   Container name.
   * @param string $execute_command
   *   Command to execute.
   */
  public function containersExec($container, $execute_command) {
    $this->validateConfig();
    $this->title('Executing in container: ' . $container, FALSE);
    $this->taskDockerComposeExecute()
      ->file($this->dockerComposeFile)
      ->projectName($this->config->get('name'))
      ->disablePseudoTty()
      ->setContainer(' ' . $container)
      ->exec($execute_command)
      ->run();
  }

  /**
   * Lists the currently active containers.
   */
  public function containersActive() {
    $ps = $this->taskDockerComposePs()
      ->file($this->dockerComposeFile)
      ->projectName($this->config->get('name'))
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    $this->say("$ps");
  }

  /**
   * Open a SSH connection to specific container.
   */
  public function containersSsh() {
    $this->title('Logging in to PHP container');
    $this->say('This command is not implemented yet. Copy and execute the following:');
    $this->io()->text('docker-compose --file ./vendor/bluesparklabs/spark/docker/docker-compose.d8.yml --project-name \'Spark Example\' exec php bash');
  }

  /**
   * Limit compose to specific container.
   *
   * @param \Robo\Contract\CommandInterface $command
   *   Docker compose commmand.
   * @param string $container
   *   Container name as defined on docker compose file.
   */
  private function limitComposeContainer(CommandInterface $command, $container) {
    $command->setService($container);
  }

}
