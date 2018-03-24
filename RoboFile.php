<?php

use Noodlehaus\Config;
use Symfony\Component\Filesystem\Filesystem;

class RoboFile extends \Robo\Tasks {

  use \Boedah\Robo\Task\Drush\loadTasks;
  use \Droath\RoboDockerCompose\Task\loadTasks;

  const DOCKER_COMPOSE_FILE_D8 = './docker/docker-compose.d8.yml';
  private $conf;
  private $workDir;

  public function __construct() {
    // Get the current working directory. Robo always changes the directory to where
    // the RoboFile is located, but we never call the commands from there.
    // See: https://github.com/consolidation/Robo/issues/413.
    $this->workDir = getenv('PWD');
    $this->conf = Config::load($this->workDir . '/.spark.yml');
  }

  private function title($message, $ellipsis = TRUE) {
    $this->io()->block('Spark ✨ ' . $this->conf->get('name') . ' — ' . $message . ($ellipsis ? '…' : ''));
  }

  public function containersStart() {
    $this->title('Starting containers');
    $this->taskDockerComposeUp()
      ->file(RoboFile::DOCKER_COMPOSE_FILE_D8)
      ->projectName($this->conf->get('name'))
      ->detachedMode()
      ->run();
  }

  public function containersDestroy() {
    $this->title('Destroying containers');
    $this->taskDockerComposeDown()
      ->file(RoboFile::DOCKER_COMPOSE_FILE_D8)
      ->projectName($this->conf->get('name'))
      ->run();
  }

  public function containersSsh() {
    $this->title('Logging in to PHP container');
    $this->say('This command is not implemented yet. Copy and execute the following:');
    $this->io()->text('docker-compose --file ./vendor/bluesparklabs/spark/docker/docker-compose.d8.yml --project-name \'Spark Example\' exec php bash');
  }

  public function drupalFiles() {
    $this->title('Preparing Drupal directories and files');
    $fs = new Filesystem();
    $drupalRoot = $this->workDir . '/www';

    foreach (['modules', 'profiles', 'themes'] as $dir) {
      if (!$fs->exists($drupalRoot . '/'. $dir)) {
        $fs->mkdir($drupalRoot . '/'. $dir);
        $fs->touch($drupalRoot . '/'. $dir . '/.gitkeep');
      }
    }

    // Copy and prepare the settings.php files for installation.
    $settingsFiles = [
      'settings.php',
      'settings.spark.php',
    ];
    if (!$fs->exists($drupalRoot . '/sites/default/settings.php')) {
      foreach ($settingsFiles as $file) {
        $fs->copy('drupal/d8/' . $file, $drupalRoot . '/sites/default/' . $file);
        $fs->chmod($drupalRoot . '/sites/default/' . $file, 0666);
      }
      $this->say('Copied settings.php files');
    }
    else {
      $this->say('settings.php files are already in place, skipping');
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists($drupalRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($drupalRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $this->say('Created files directory');
    }
    else {
      $this->say('Files directory is already in place, skipping');
    }
  }

  public function drupalInstall() {
    $this->title('Installing Drupal');
    $this->taskDrushStack()
      ->drupalRootDirectory($this->workDir . '/www' )
      //->drupalRootDirectory('./www' )
      ->siteName($this->conf->get('name'))
      ->accountName('admin')
      ->accountPass('admin')
      ->siteInstall('standard')
      ->run();
  }

  public function containersDrupalInstall() {
    $this->title('Running Drupal install inside the PHP container');
    $this->taskDockerComposeExecute()
      ->file(RoboFile::DOCKER_COMPOSE_FILE_D8)
      ->projectName($this->conf->get('name'))
      ->setContainer(' php')
      // @todo Somehow we should invoke the drupalInstall() command here.
      ->exec('drush site-install -y standard --account-name=admin --account-pass=admin --site-name="Spark Example"')
      ->disablePseudoTty()
      ->run();
  }

}
