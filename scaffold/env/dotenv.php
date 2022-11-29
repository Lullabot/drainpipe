<?php

/**
 * @file
 * Loads .env.defaults and .env files.
 *
 * See autoload.files in composer.json and
 * https://getcomposer.org/doc/04-schema.md#files.
 */

use Dotenv\Dotenv;

$cwd = getcwd();
$env_files = [];
if (file_exists(join(DIRECTORY_SEPARATOR, [$cwd, '.env']))) {
    $env_files[] = '.env';
}
if (file_exists(join(DIRECTORY_SEPARATOR, [$cwd, '.env.defaults']))) {
    $env_files[] = '.env.defaults';
}
$dotenv = Dotenv::createUnsafeImmutable($cwd, $env_files, false);
$dotenv->load();
