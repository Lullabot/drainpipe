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
$dotenv = Dotenv::createImmutable(__DIR__, '.env.defaults');
$dotenv->load();
$dotenv = Dotenv::createImmutable(__DIR__, '.env');
$dotenv->load();
