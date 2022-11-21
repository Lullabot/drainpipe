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

// Load both .env.defaults and .env so we can use the immutable class.
// Otherwise when we load .env after .env.defaults it's not able to overwrite
// any variables, however loading with the mutable class means real environment
// variables won't take precedence.
$defaults = file_get_contents(join(DIRECTORY_SEPARATOR, [$cwd, '.env.defaults']));
$overrides = file_get_contents(join(DIRECTORY_SEPARATOR, [$cwd, '.env']));

Dotenv::parse($defaults . "\n" . "$overrides");
