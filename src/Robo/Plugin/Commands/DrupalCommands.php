<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;

class DrupalCommands extends \BluesparkLabs\Spark\Robo\Tasks {

  use \Droath\RoboDockerCompose\Task\loadTasks;

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

  /**
   * Backup Drupal files and database to Amazon S3.
   *
   * Expected usage:
   *
   *     $ composer run spark drupal:backup --timeout=0
   *
   * Example configuration in .spark.yml:
   *
   * ```
   *  command:
   *    drupal:
   *      backup:
   *        options:
   *          truncate: cache,cache_*,sessions,watchdog
   *          skip: migrate_*
   *          files:
   *            - web/sites/default/files
   *            - private
   *          exclude:
   *            - css
   *            - js
   *            - styles
   *            - xmlsitemap
   *            - backup_migrate
   *            - ctools
   *            - php
   * ```
   *
   * @param array $opts
   * @options $truncate  A comma-separated list of tables from which to
   *                     truncate values in the db dump. This maps to the
   *                     drush sql-dump --structure-table-list option.
   *                     Default value is 'cache,cache_*,sessions,watchdog'.
   *          $skip      A comma-separated list of tables from which to
   *                     exclude from the db dump.
   *          $files     A string or array of paths/to/files/or/folders to
   *                     include in the tarball. Paths should be relative
   *                     to the project root directory and not to the webroot.
   *          $exclude   A string or array of filenames/foldernames to
   *                     exclude from the tarball.
   */
  public function drupalBackup($opts = [
    'truncate' => 'cache,cache_*,watchdog,sessions',
    'skip' => '',
    'files' => null,
    'exclude' => null
  ]) {

    // Create unique backup filenames using application name, current
    // environment, and date and time stamp.
    $timestamp = $this->executeDateTime;
    $name = $this->config->get('name');
    $environment = getenv('ENVIRONMENT') ?: 'default';
    $filename = $this->cleanFileName("{$name}-{$environment}-{$timestamp}");
    $dumpfile = "{$filename}.sql";
    $tarfile = "{$filename}.tgz";

    $this->title('Executing Drupal backup command');
    $this->taskSparkExec('drush', ["sql-dump --gzip --result-file={$dumpfile} --structure-tables-list={$opts['truncate']} --skip-tables-list={$opts['skip']}"]);

    // Backup Drupal files folder.
    $this->title('Creating Drupal files tarball');
    $tarTask = $this->taskExec('tar');
    $tarTask->dir($this->workDir);

    // Directories/files to exclude from tarball.
    if (empty($opts['exclude'])) {
      $opts['exclude'] = [
        'css',
        'js',
        'ctools',
        'php',
        'styles',
        'backup_migrate',
        'xmlsitemap',
      ];
    }
    $tarTask->optionList('exclude', $opts['exclude'], '=');

    // Exeution options: gzip, create, verbose, filename
    $tarTask->option("-zcf", "{$tarfile}", " ");

    // Files and directories to include.
    if (empty($opts['files'])) {
      $opts['files'] = $this->webRoot . '/sites/default/files';
    }
    $tarTask->args($opts['files']);

    $this->say(" ... be patient, this may take some time ... ");
    $this->say(" ... if the operation times out try with --timeout=0 ... ");

    $tarTask->run();

    // @todo implement sync to s3

    // @todo implement cleanup s3 dumps older than 15 days
  }
}
