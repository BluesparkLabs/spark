<?php

namespace BluesparkLabs\Spark\Robo;

use Noodlehaus\Config;
use Noodlehaus\Exception;
use Noodlehaus\Exception\FileNotFoundException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;

class Tasks extends \Robo\Tasks {

  const CONFIG_FILE_NAME = '.spark.yml';

  protected $config;
  protected $workDir;
  protected $dockerComposeFile;
  protected $roboExecutable;

  public function __construct() {
    $this->stopOnFail(true);
    // Get the current working directory. Robo always changes the directory to where
    // the RoboFile is located, but we never call the commands from there.
    // See: https://github.com/consolidation/Robo/issues/413.
    $this->workDir = getenv('PWD');
    // Load config file from the project: .spark.yml.
    try {
      $this->config = Config::load($this->workDir . '/' . Tasks::CONFIG_FILE_NAME);
    }
    catch (FileNotFoundException $exception) {
      throw new \Exception('Missing configuration file: ' . Tasks::CONFIG_FILE_NAME);
    }
    $this->dockerComposeFile = './docker/docker-compose.' . $this->config->get('platform') . '.yml';
    $this->roboExecutable = $this->workDir . '/vendor/bin/robo';
  }

  protected function title($message, $ellipsis = TRUE) {
    $this->io()->block('Spark ✨ ' . $this->config->get('name') . ' — ' . $message . ($ellipsis ? '…' : ''));
  }

  protected function validateConfig() {
    // Although it would be great, we're not calling this method from the
    // constructor, because the messages are suppressed from there. So instead each
    // command needs to invoke this method.
    try {
      v::key('name', v::stringType()->length(1,32))
        ->key('platform', v::in(['drupal8']))
        ->assert($this->config->all());
    }
    catch (NestedValidationException $exception) {
      $this->yell('There are problems with your .spark.yml file.', 40, 'red');
      $this->io()->error($exception->getMessages());
      $this->io()->note('📖 Documentation: https://github.com/BluesparkLabs/spark/wiki/Configuration');
      throw new Exception('Invalid configuration for Spark.');
    }
  }

  protected function taskSparkExec($command, $args = []) {
    // Wrap all arguments in double quotes.
    foreach ($args as &$arg) {
      $arg = sprintf('"%s"', $arg);
    }
    $this->taskExec(sprintf('composer run -d %s robo %s %s', $this->workDir, $command, implode(' ', $args)))->run();
  }
}
