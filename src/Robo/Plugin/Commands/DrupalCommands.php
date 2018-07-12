<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

use Symfony\Component\Filesystem\Filesystem;

class DrupalCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  public function drupalFiles() {
    $this->title('Preparing Drupal directories and files');
    $fs = new Filesystem();

    foreach (['modules', 'profiles', 'themes'] as $dir) {
      if (!$fs->exists($this->webRoot . '/'. $dir)) {
        $fs->mkdir($this->webRoot . '/'. $dir);
        $fs->touch($this->webRoot . '/'. $dir . '/.gitkeep');
      }
    }

    // Copy and prepare the settings.php files for installation.
    $settingsFiles = [
      'settings.php',
      'settings.spark.php',
    ];
    if (!$fs->exists($this->webRoot . '/sites/default/settings.php')) {
      foreach ($settingsFiles as $file) {
        $fs->copy('drupal/d8/' . $file, $this->webRoot . '/sites/default/' . $file);
        $fs->chmod($this->webRoot . '/sites/default/' . $file, 0666);
      }
      $this->say('Copied settings.php files');
    }
    else {
      $this->say('settings.php files are already in place, skipping');
    }

    // Create the files directory with chmod 0777
    if (!$fs->exists($this->webRoot . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($this->webRoot . '/sites/default/files', 0777);
      umask($oldmask);
      $this->say('Created files directory');
    }
    else {
      $this->say('Files directory is already in place, skipping');
    }
  }

  public function drupalInstall() {
    $this->taskSparkExec('drush', ['site-install -y standard --account-name=admin --account-pass=admin']);
  }
}
