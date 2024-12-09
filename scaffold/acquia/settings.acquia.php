<?php

/**
 * @file
 * Setup Acquia Cloud settings.
 */

if (getenv('AH_SITE_ENVIRONMENT')) {
  // If trusted_reverse_proxy_ips is not defined, fail gracefully.
  // phpcs:ignore
  $trusted_reverse_proxy_ips = isset($trusted_reverse_proxy_ips) ? $trusted_reverse_proxy_ips : '';
  if (!is_array($trusted_reverse_proxy_ips)) {
    $trusted_reverse_proxy_ips = [];
  }

  // Tell Drupal whether the client arrived via HTTPS. Ensure the
  // request is coming from our load balancers by checking the IP address.
  if (getenv('HTTP_X_FORWARDED_PROTO') === 'https'
    && getenv('REMOTE_ADDR')
    && in_array(getenv('REMOTE_ADDR'), $trusted_reverse_proxy_ips, TRUE)) {
    putenv("HTTPS=on");
  }
  $x_ips = getenv('HTTP_X_FORWARDED_FOR') ? explode(',', getenv('HTTP_X_FORWARDED_FOR')) : [];
  $x_ips = array_map('trim', $x_ips);

  // Add REMOTE_ADDR to the X-Forwarded-For in case it's an internal AWS address.
  if (getenv('REMOTE_ADDR')) {
    $x_ips[] = getenv('REMOTE_ADDR');
  }

  // Check firstly for the bal and then check for an internal IP immediately.
  $settings['reverse_proxy_addresses'] = $settings['reverse_proxy_addresses'] ?? [];
  $ip = array_pop($x_ips);
  if ($ip) {
    if (in_array($ip, $trusted_reverse_proxy_ips)) {
      if (!in_array($ip, $settings['reverse_proxy_addresses'])) {
        $settings['reverse_proxy_addresses'][] = $ip;
      }
      // We have a reverse proxy so turn the setting on.
      $settings['reverse_proxy'] = TRUE;

      // Get the next IP to test if it is internal.
      $ip = array_pop($x_ips);
      if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
        if (!in_array($ip, $settings['reverse_proxy_addresses'])) {
          $settings['reverse_proxy_addresses'][] = $ip;
        }
      }
    }
  }

  /**
   * Site path.
   *
   * @var string $site_path
   * This is always set and exposed by the Drupal Kernel.
   */
  $site_name = EnvironmentDetector::getSiteName($site_path);
  $ah_group = getenv('AH_SITE_GROUP');

  // The default site uses ah_group-settings.inc.
  if ($site_name === 'default') {
    $site_name = $ah_group;
  }

  // Acquia Cloud does not support periods in db names.
  $site_name = str_replace('.', '_', $site_name);

  include __DIR__ . "/var/www/site-php/$ah_group/$site_name-settings.inc";
}
