<?php

$sites['drupal_chrome'] = 'chrome';
$sites['drupal_firefox'] = 'firefox';

if (getenv('IS_DDEV_PROJECT') == 'true') {
  $DDEV_SITENAME = getenv('DDEV_SITENAME');
  $DDEV_TLD = getenv('DDEV_TLD');
  $sites["chrome.$DDEV_SITENAME.$DDEV_TLD"] = 'chrome';
  $sites["firefox.$DDEV_SITENAME.$DDEV_TLD"] = 'firefox';
}
