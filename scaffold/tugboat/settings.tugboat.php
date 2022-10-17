<?php

if (getenv('TUGBOAT_REPO') !== FALSE) {
  $databases['default']['default'] = [
    'database' => 'tugboat',
    'username' => 'tugboat',
    'password' => 'tugboat',
    'prefix' => '',
    'host' => 'mariadb',
    'port' => '3306',
    'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
    'driver' => 'mysql',
  ];
}
