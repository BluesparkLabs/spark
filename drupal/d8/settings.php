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

// @todo Figure out best way to decide which settings.php to include.
require_once(__DIR__ . '/settings.spark.php');
