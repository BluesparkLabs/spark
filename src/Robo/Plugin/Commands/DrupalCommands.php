<?php

namespace BluesparkLabs\Spark\Robo\Plugin\Commands;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;
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
   *          bucket: bsp-myproject
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
   * @options $bucket    (Required) The S3 bucket destination for the backup.
   *                     E.g. 'bsp-myproject'
   *          $region    The AWS region to connect to. If left blank, the
   *                     default value is 'us-east-1'. For a list of available
   *                     regions, see http://bit.ly/s3-regions.
   *          $profile   The AWS profile to use for connection credentials.
   *                     Default value is 'default'. The AWS SDK will first
   *                     try to load credentials from environment variables
   *                     (http://bit.ly/aws-php-creds). If not found, and if
   *                     this option is left blank, the SDK then looks for
   *                     the default credentials in the `~/.aws/credentials`
   *                     file. Finally, if you specify a custom profile
   *                     value, the SDK loads credentials from that profile.
   *                     See http://bit.ly/aws-creds-file for formatting info.
   *          $keep      A string representing a relative amount of time to
   *                     keep backups. The string must be parsable by PHP
   *                     `strtotime`. The default value is '15 days', which
   *                     is the recommendation for GDPR compliance. Files
   *                     found in the backup folder on S3 that are older than
   *                     this time will be removed.  New files uploaded to S3
   *                     will have an Expires value set to now plus the
   *                     specified time.  WARNING: be very careful modifying
   *                     the value of this option as it can and will delete
   *                     existing backups.
   *          $truncate  A comma-separated list of tables from which to
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
    'bucket' => InputOption::VALUE_REQUIRED,
    'region' => 'us-east-1',
    'profile' => 'default',
    'keep' => '15 days',
    'truncate' => 'cache,cache_*,watchdog,sessions',
    'skip' => '',
    'files' => null,
    'exclude' => null
  ]) {

    // Create unique backup filenames using application name, current
    // environment, and date and time stamp.
    $timestamp = $this->executeDateTime;
    $timelimit = strtotime('now - ' . $opts['keep'] . ' UTC');
    $application = $this->cleanFileName($this->config->get('name'));
    $environment = getenv('ENVIRONMENT') ?: 'default';
    $prefix = $this->cleanFileName("{$application}-{$environment}");
    $filename = $this->cleanFileName("{$prefix}-{$timestamp}");
    $dumpfile = "{$filename}.sql";
    $tarfile = "{$filename}.tgz";

    // Create a tmp directory.
    $tmpDirTask = $this->taskTmpDir(__FUNCTION__);
    $tmpDir = $tmpDirTask->getPath() . '/';
    $tmpDirTask->run();

    $this->title('Executing Drupal backup command');
    $this->taskSparkExec('drush', ["sql-dump --gzip --result-file={$tmpDir}{$dumpfile} --structure-tables-list={$opts['truncate']} --skip-tables-list={$opts['skip']}"]);

    // The above drush command modifies the dumpfile name upon gzipping.
    $dumpfile = $dumpfile . '.gz';

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
    $tarTask->option("-zcf", "{$tmpDir}{$tarfile}", " ");

    // Files and directories to include.
    if (empty($opts['files'])) {
      $opts['files'] = $this->webRoot . '/sites/default/files';
    }
    $tarTask->args($opts['files']);

    $this->say(" ... be patient, this may take some time ... ");
    $this->say(" ... if the operation times out try with --timeout=0 ... ");

    $tarTask->run();

    try {
      // Connect to S3.

      // The following options are required.
      $s3ClientOptions = [
        'version' => '2006-03-01',
        'region' => $opts['region']
      ];
      // Add the AWS profile option if provided.
      if (!empty($opts['profile'])) {
        $s3ClientOptions['profile'] = $opts['profile'];
      }
      $s3Client = new S3Client($s3ClientOptions);

      $this->title("Uploading database to Amazon S3 bucket: {$opts['bucket']}");
      $result = $s3Client->putObject([
        'Bucket'     => $opts['bucket'],
        'Key'        => $application . '/' . $dumpfile,
        'SourceFile' => $tmpDir . $dumpfile,
      ]);
      $this->say("Database uploaded to: {$result['ObjectURL']}");

      $this->title("Uploading files tarball to Amazon S3 bucket: {$opts['bucket']}");
      $this->say(" ... be patient, this may take some time ... ");
      $this->say(" ... if the operation times out try with --timeout=0 ... ");
      $result = $s3Client->putObject([
        'Bucket'     => $opts['bucket'],
        'Key'        => $application . '/' . $tarfile,
        'SourceFile' => $tmpDir . $tarfile,
      ]);
      $this->say("Files tarball uploaded to: {$result['ObjectURL']}");

      // Delete backup files that are older than the desired time limit.
      $this->title("Delete S3 backups older than {$opts['keep']} in bucket: {$opts['bucket']}");

      // Note: this will list up to a maximum of 1000 files, I hope there
      // wouldn't be more than that.
      $result = $s3Client->listObjects([
        'Bucket' => $opts['bucket'],
        'Marker' => $application . '/',
        'Prefix' => $application . '/' . $prefix,
      ]);
      $confirm_delete = '';
      foreach ($result['Contents'] as $file) {
        $filedate = strtotime($file['LastModified']);
        if ($filedate < $timelimit) {
          $this->say("{$file['Key']} is more than {$opts['keep']} old.");
          if (empty($confirm_delete) || !in_array($confirm_delete, ['all', 'none'])) {
            $confirm_delete = $this->askDefault("{$file['Key']} is more than {$opts['keep']} old. Delete it? (Y/n/all/none)", 'Y');
            $confirm_delete = strtolower($confirm_delete);
          }
          if (in_array($confirm_delete, ['y', 'all'])) {
            $result = $s3Client->deleteObject([
              'Bucket' => $opts['bucket'],
              'Key' => $file['Key'],
            ]);
            $this->say("{$file['Key']} has been deleted.");
          }
        }
      }

    } catch (S3Exception $e) {
      $this->say($e->getMessage());
    }

    // Once successfully uploaded, delete the local backup files.
    $this->title('Cleaning up temporary local backup files');
    $tmpDirTask->complete();
    $this->say('Drupal backup completed successfully!');
  }
}
