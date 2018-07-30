<?php

$update_free_access = FALSE;
$drupal_hash_salt = '';
$config_directories = [];
$databases = [];

ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cookie_lifetime', 2000000);

$settings['container_yamls'][] = $app_root . '/' . $site_path . '/services.yml';
$settings['file_scan_ignore_directories'] = ['node_modules'];
$settings['entity_update_batch_size'] = 50;

$databases['default']['default'] = array (
  'database' => 'spark',
  'username' => 'spark',
  'password' => 'spark',
  'prefix' => '',
  'host' => 'db',
  'port' => '3306',
  'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
  'driver' => 'mysql',
);
$settings['hash_salt'] = '0JtDW_PcXiWeKyIRpAtUu78GsI1l8qdhZd60hnPAKeauqU2jQPgx3tfFcOcUkkO4XZS1BGjaWg';
$settings['install_profile'] = 'standard';
$config_directories['sync'] = 'sites/default/files/config_wPhmDI7hffAVgwHkFdU_9Jjx_IMXnIwliyeOf8BZBNsYdiL87K9DFHal71kGm31moCtCOnS4-w/sync';

// @todo Figure out best way to decide which settings.php to include.
require_once(__DIR__ . '/settings.spark.php');
