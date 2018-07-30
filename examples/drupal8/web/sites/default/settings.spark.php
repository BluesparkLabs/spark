<?php

$databases = array(
  'default' => array(
    'default' => array(
      'database' => 'spark',
      'username' => 'spark',
      'password' => 'spark',
      'host' => '127.0.0.1',
      'driver' => 'mysql',
    ),
  ),
);

// The SPARK_PHP environment variable is set in the D8 Docker Compose file
// for the PHP container. That's how we know if PHP is being executed in the
// container or on the local machine.
if (getenv('SPARK_PHP')) {
  // From the PHP container the database hostname is 'db'.
  $databases['default']['default']['host'] = 'db';
}
else {
  // From outside of the PHP container the database is exposed on port 7501.
  $databases['default']['default']['port'] = '7501';
}
