<?php
// phpcs:ignoreFile
// due to https://www.drupal.org/project/drupal/issues/445012 creating old style
// arrays.

/**
 * @file
 * Drupal settings for Firefox browser tests.
 */

include __DIR__ . '/../default/settings.php';

$databases['default']['default'] = [
    'database' => 'firefox',
    'username' => 'db',
    'password' => 'db',
    'host' => 'db',
    'driver' => 'mysql',
    'port' => 3306,
    'prefix' => '',
];
