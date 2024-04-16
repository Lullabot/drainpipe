<?php

/**
 * @file
 * Loads .env.defaults and .env files.
 *
 * See autoload.files in composer.json and
 * https://getcomposer.org/doc/04-schema.md#files.
 */

use Dotenv\Dotenv;

$cwd = join(DIRECTORY_SEPARATOR, [DRUPAL_ROOT, '..']);
$env_files = [];

if (file_exists(join(DIRECTORY_SEPARATOR, [$cwd, '.env.defaults']))) {
    $env_files[] = '.env.defaults';
}
if (file_exists(join(DIRECTORY_SEPARATOR, [$cwd, '.env']))) {
    $env_files[] = '.env';
}
if (!empty($env_files)) {
    $dotenv = Dotenv::createUnsafeImmutable($cwd, $env_files, false);
    $dotenv->load();
}
