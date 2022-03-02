<?php
// phpcs:ignoreFile
// due to https://www.drupal.org/project/drupal/issues/445012 creating old style
// arrays.

/**
 * @file
 * Drupal settings for Chrome browser tests.
 */

include __DIR__ . '/../default/settings.php';

$host = "db";
$port = 3306;
// If DDEV_PHP_VERSION is not set but IS_DDEV_PROJECT *is*, it means we're running (drush) on the host,
// so use the host-side bind port on docker IP
if (empty(getenv('DDEV_PHP_VERSION') && getenv('IS_DDEV_PROJECT') == 'true')) {
  $host = "127.0.0.1";
  $port = getenv('DDEV_HOST_DB_PORT');
}

$databases['default']['default'] = [
  'database' => 'chrome',
  'username' => 'db',
  'password' => 'db',
  'host' => $host,
  'driver' => 'mysql',
  'port' => $port,
  'prefix' => '',
];
