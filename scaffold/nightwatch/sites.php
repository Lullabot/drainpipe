<?php

// phpcs:ignoreFile

/**
 * @file
 * Multi-site configuration.
 *
 * Allows separate installs for Chrome and Firefox Nightwatch testing.
 */

if (getenv('IS_DDEV_PROJECT') == 'true') {
  $sites['drupal_chrome'] = 'chrome';
  $sites['drupal_firefox'] = 'firefox';
  $ddev_sitename = getenv('DDEV_SITENAME');
  $ddev_tld = getenv('DDEV_TLD');
  $sites["chrome.$ddev_sitename.$ddev_tld"] = 'chrome';
  $sites["firefox.$ddev_sitename.$ddev_tld"] = 'firefox';
}
