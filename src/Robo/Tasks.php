<?php

namespace BluesparkLabs\Spark\Robo;

use Dotenv\Dotenv;
use Noodlehaus\Config;
use Noodlehaus\Exception;
use Noodlehaus\Exception\FileNotFoundException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Robo;

class Tasks extends \Robo\Tasks {

  const CONFIG_FILE_NAME = '.spark.yml';

  protected $config;
  protected $workDir;
  protected $webRoot;
  protected $dockerComposeFile;
  protected $roboExecutable;

  public function __construct() {
    $this->stopOnFail(true);
    // Get the current working directory. Robo always changes the directory
    // to where  the RoboFile is located, but we never call the commands from
    // there. See https://github.com/consolidation/Robo/issues/413
    $this->workDir = getenv('PWD');

    // Load environment variables from `.env` file into `getenv()` for use
    // by other tasks and commands.
    $dotenv = new Dotenv($this->workDir);
    $dotenv->load();

    // Load config file from the project: .spark.yml.
    try {
      $spark_config = $this->workDir . '/' . Tasks::CONFIG_FILE_NAME;
      $this->config = Config::load($spark_config);

      // The spark config file may also contain default options for Robo
      // commands and tasks, so load it into Robo as well.
      // See https://robo.li/getting-started/#configuration for formatting
      Robo::loadConfiguration([$spark_config]);
    }
    catch (FileNotFoundException $exception) {
      throw new \Exception('Missing configuration file: ' . Tasks::CONFIG_FILE_NAME);
    }
    $this->dockerComposeFile = './docker/docker-compose.' . $this->config->get('platform') . '.yml';
    $this->roboExecutable = $this->workDir . '/vendor/bin/robo';

    $this->webRoot = $this->workDir . '/web';
    if ($webRoot = $this->config->get('webroot')) {
      $separator = (substr($webRoot, 0, 1) === '/') ? '' : '/';
      $this->webRoot = $this->workDir . $separator . $webRoot;
    }
  }

  protected function title($message, $ellipsis = TRUE) {
    $this->io()->block('Spark ✨ ' . $this->config->get('name') . ' — ' . $message . ($ellipsis ? '…' : ''));
  }

  protected function validateConfig() {
    // Although it would be great, we're not calling this method from the
    // constructor, because the messages are suppressed from there. So
    // instead each command needs to invoke this method.
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

  protected function taskSparkContainerExec($container, $command) {
    if (is_array($command)) {
      $command = implode('; ', $command);
    }
    $cmd_tpl = 'composer run -d %s robo containers:exec \'%s\' \'/bin/sh -c "%s"\'';
    $this->taskExec(sprintf($cmd_tpl, $this->workDir, $container, $command))->run();
  }

  protected function containerExists($container) {
    $this->validateConfig();
    $project = $this->config->get('name');
    $ps = $this->taskDockerComposePs()
      ->file($this->dockerComposeFile)
      ->projectName($project)
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    $container_name = strtolower($project) . '_' . $container;
    $regex = '/' . $container_name . '/m';
    return preg_match($regex, $ps);
  }

}
