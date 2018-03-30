<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

class DrushCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  use \Droath\RoboDockerCompose\Task\loadTasks;

  private $drush;
  private $root;
  private $isContainer;
  private $commandBase;

  public function __construct() {
    parent::__construct();

    $this->setIsContainer();
    if ($this->isContainer) {
      $this->drush = '../vendor/bin/drush';
      $this->root = '.';
    }
    else {
      $this->drush = $this->workDir . '/vendor/bin/drush';
      $this->root = $this->workDir . '/www';
    }
    $this->setCommandBase();
  }

  public function drush(array $args) {
    $this->validateConfig();
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

  private function setIsContainer() {
    // @todo Implement.
    $this->isContainer = TRUE;
  }

  private function setCommandBase() {
    $this->commandBase = sprintf('%s --root=%s', $this->drush, $this->root);
  }

}