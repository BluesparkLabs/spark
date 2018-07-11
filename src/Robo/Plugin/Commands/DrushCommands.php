<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

class DrushCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  use \Droath\RoboDockerCompose\Task\loadTasks;

  private $drush;
  private $root;
  private $isContainer;
  private $commandBase;

  private function prepare() {
    $this->isContainer = $this->containerExists('php');
    if ($this->isContainer) {
      $this->drush = '../vendor/bin/drush';
      $this->root = '.';
    }
    else {
      $this->drush = $this->workDir . '/vendor/bin/drush';
      $this->root = $this->workDir . '/web';
    }
    $this->commandBase = sprintf('%s --root=%s', $this->drush, $this->root);
  }

  public function drush(array $args) {
    $this->prepare();
    $this->title('Executing Drush command');
    $command = $this->commandBase . ' ' . implode(' ', $args);

    if ($this->isContainer) {
      $this->say('🚢 Running inside the PHP container');
      $this->taskDockerComposeExecute()
        ->file($this->dockerComposeFile)
        ->projectName($this->config->get('name'))
        ->setContainer(' php')
        ->exec($command)
        ->disablePseudoTty()
        ->run();
    }
    else {
      $this->say('💻 Running locally');
      $this->taskExec($command)->run();
    }
  }
}
