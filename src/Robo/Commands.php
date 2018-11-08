<?php

namespace BluesparkLabs\Spark\Robo;

use BluesparkLabs\Spark\Robo\loadTasks;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Noodlehaus\Config;
use Noodlehaus\Exception;
use Noodlehaus\Exception\FileNotFoundException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator as v;
use Robo\Contract\VerbosityThresholdInterface;
use Robo\Robo;

class Commands extends \Robo\Tasks {

  use loadTasks;

  const CONFIG_FILE_NAME = '.spark.yml';
  const CONFIG_LOCAL_FILE_NAME = '.spark.local.yml';

  protected $config;
  protected $config_local;
  protected $workDir;
  protected $webRoot;
  protected $dockerComposeFile;
  protected $roboExecutable;
  protected $executeDateTime;

  public function __construct() {
    $this->stopOnFail(true);

    // Store a date/time stamp for use across tasks, useful for creating
    // unique output filenames.
    $this->executeDateTime = gmdate('Y-m-d--H-i-s', time());

    // Get the current working directory. Robo always changes the directory
    // to where  the RoboFile is located, but we never call the commands from
    // there. See https://github.com/consolidation/Robo/issues/413
    $this->workDir = getenv('PWD');

    // Load environment variables from `.env` file into `getenv()` for use
    // by other tasks and commands.
    try {
      $dotenv = new Dotenv($this->workDir);
      $dotenv->load();
    } catch (InvalidPathException $e) {
      // Silently fail if .env file doesnt exist.
    }

    // Load config file from the project: .spark.yml.
    try {
      $spark_config = $this->workDir . '/' . Commands::CONFIG_FILE_NAME;
      $this->config = Config::load($spark_config);

      // The spark config file may also contain default options for Robo
      // commands and tasks, so load it into Robo as well.
      // See https://robo.li/getting-started/#configuration for formatting
      Robo::loadConfiguration([$spark_config]);
    }
    catch (FileNotFoundException $exception) {
      throw new \Exception('Missing configuration file: ' . Commands::CONFIG_FILE_NAME);
    }

    // Load local config file if exists: .spark.local.yml.
    $spark_config_local = $this->workDir . '/' . Commands::CONFIG_LOCAL_FILE_NAME;
    if (file_exists($spark_config_local)) {
      $this->config_local = Config::load($spark_config_local);
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
        ->key('platform', v::in(['drupal7', 'drupal8']))
        ->assert($this->config->all());

      if ($this->config->has('database-export.sanitization')) {
        v::key('rules', v::arrayType())
          ->assert($this->config->get('database-export.sanitization'));

        if ($this->config->has('database-export.sanitization.faker-locale')) {
          v::alpha('_')->noWhitespace()->assert($this->config->get('database-export.sanitization.faker-locale'));
        }
      }
      if ($this->config->has('database-export.exclude-tables')) {
        v::arrayType()->assert($this->config->get('database-export.exclude-tables'));
      }
      if ($this->config->has('database-export.no-data')) {
        v::arrayType()->assert($this->config->get('database-export.no-data'));
      }
    }
    catch (NestedValidationException $exception) {
      $this->yell('There are problems with your .spark.yml file.', 40, 'red');
      $this->io()->error($exception->getMessages());
      $this->io()->note('📖  Documentation: https://github.com/BluesparkLabs/spark');
      throw new Exception('Invalid configuration for Spark.');
    }

    // Validate .spark.local.yml if it exists.
    if (!$this->config_local) {
      return;
    }

    try {
      if ($this->config_local->has('services.mysql.connection')) {
        v::key('host', v::oneOf(v::stringType()->noWhitespace(), v::ip()))
          ->key('port', v::intVal())
          ->key('dbname', v::stringType()->length(1,64))
          ->key('user', v::stringType()->length(1,32))
          ->key('password', v::stringType()->length(1,64))
          ->assert($this->config_local->get('services.mysql.connection'));
      }

      if ($this->config_local->has('environment-name')) {
        v::stringType()->length(1, 32)->assert($this->config_local->get('environment-name'));
      }
    }
    catch (NestedValidationException $exception) {
      $this->yell('There are problems with your .spark.local.yml file.', 40, 'red');
      $this->io()->error($exception->getMessages());
      $this->io()->note('📖  Documentation: https://github.com/BluesparkLabs/spark');
      throw new Exception('Invalid configuration for Spark.');
    }
  }

  protected function taskSparkExec($command, $args = []) {
    // Wrap all arguments in double quotes.
    foreach ($args as &$arg) {
      $arg = sprintf('"%s"', $arg);
    }
    $this->taskExec(sprintf('composer run -d %s spark %s %s', $this->workDir, $command, implode(' ', $args)))->run();
  }

  protected function taskSparkContainerExec($container, $command) {
    if (is_array($command)) {
      $command = implode('; ', $command);
    }
    $cmd_tpl = 'composer run -d %s spark containers:exec \'%s\' \'/bin/sh -c "%s"\'';
    $this->taskExec(sprintf($cmd_tpl, $this->workDir, $container, $command))->run();
  }

  protected function containerExists($container) {
    $this->validateConfig();
    $ps = $this->taskDockerComposePs()
      ->file($this->dockerComposeFile)
      ->projectName($this->config->get('name'))
      ->setVerbosityThreshold(VerbosityThresholdInterface::VERBOSITY_DEBUG)
      ->printOutput(FALSE)
      ->run()
      ->getMessage();
    $regex = '/' . $this->getContainerName($container) . '/m';
    return preg_match($regex, $ps);
  }

  /**
   * Returns Docker Compose-style container name with project name as prefix.
   */
  protected function getContainerName($container) {
    $this->validateConfig();
    // Project name is defined in a nice, human-readable style, so lowercase it
    // first.
    $project_lowercase = strtolower($this->config->get('name'));
    // Replace all spaces to match the behavior of Docker Compose. Then finally
    // append container name.
    return str_replace(' ', '', $project_lowercase) . '_' . $container;
  }

  /**
   * Converts random strings to clean filenames.
   *
   * The cleanups are loosly based on drupal_clean_css_identifier().
   * - prefer dash-separated words.
   * - strip special characters.
   * - down-case alphabetical letters.
   */
  protected function cleanFileName($identifier) {

    // Convert or strip certain special characters, by convention.
    $filter = [
      ' ' => '-',
      '_' => '-',
      '/' => '-',
      '[' => '-',
      ']' => '',
    ];
    $identifier = strtr($identifier, $filter);

    // Valid characters in a clean filename identifier are:
    // - the hyphen (U+002D)
    // - the period (U+002E)
    // - a-z (U+0030 - U+0039)
    // - A-Z (U+0041 - U+005A)
    // - the underscore (U+005F)
    // - 0-9 (U+0061 - U+007A)
    // - ISO 10646 characters U+00A1 and higher
    // We strip out any character not in the above list
    $identifier = preg_replace('/[^\\x{002D}\\x{002E}\\x{0030}-\\x{0039}\\x{0041}-\\x{005A}\\x{005F}\\x{0061}-\\x{007A}\\x{00A1}-\\x{FFFF}]/u', '', $identifier);

    // Convert everything to lowercase
    return strtolower($identifier);
  }
}
