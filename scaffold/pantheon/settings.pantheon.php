<?php

/**
 * @file
 * Drupal settings overrides for Pantheon.
 */

/**
 * Database credentials.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/8
 */

if (isset($_SERVER['PRESSFLOW_SETTINGS'])) {
    $pressflow_settings = json_decode($_SERVER['PRESSFLOW_SETTINGS'], TRUE);
    foreach ($pressflow_settings as $key => $value) {
        // One level of depth should be enough for $conf and $database.
        if ($key == 'conf') {
            foreach ($value as $conf_key => $conf_value) {
                $conf[$conf_key] = $conf_value;
            }
        }
        elseif ($key == 'databases') {
            // Protect default configuration but allow the specification of
            // additional databases. Also, allows fun things with 'prefix' if they
            // want to try multisite.
            if (!isset($databases) || !is_array($databases)) {
                $databases = [];
            }
            $databases = array_replace_recursive($databases, $value);
        }
        else {
            $$key = $value;
        }
    }
}

/**
 * Handle Hash Salt Value from Drupal.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/10
 */
$settings['hash_salt'] = getenv('DRUPAL_HASH_SALT');

/**
 * Define appropriate location for tmp directory.
 *
 * Issue: https://github.com/pantheon-systems/drops-8/issues/114
 */
$settings["file_temp_path"] = $_SERVER['HOME'] . '/tmp';

/**
 * Panbtheon tmp directory.
 *
 * Place Twig cache files in the Pantheon rolling temporary directory.
 * A new rolling temporary directory is provided on every code deploy,
 * guaranteeing that fresh twig cache files will be generated every time.
 * Note that the rendered output generated from the twig cache files
 * are also cached in the database, so a cache clear is still necessary
 * to see updated results after a code deploy.
 */
if (getenv('PANTHEON_ROLLING_TMP') !== NULL && getenv('PANTHEON_DEPLOYMENT_IDENTIFIER') !== NULL) {
    // Relocate the compiled twig files to <binding-dir>/tmp/ROLLING/twig.
    // The location of ROLLING will change with every deploy.
    $settings['php_storage']['twig']['directory'] = getenv('PANTHEON_ROLLING_TMP');
    // Ensure that the compiled twig templates will be rebuilt whenever the
    // deployment identifier changes.  Note that a cache rebuild is also
    // necessary.
    $settings['deployment_identifier'] = getenv('PANTHEON_DEPLOYMENT_IDENTIFIER');
    $settings['php_storage']['twig']['secret'] = getenv('DRUPAL_HASH_SALT') . $settings['deployment_identifier'];
}

/**
 * Install the Pantheon Service Provider.
 *
 * Hook Pantheon services into Drupal. This service provider handles operations
 * such as clearing the Pantheon edge cache whenever the Drupal cache is
 * rebuilt.
 */
$GLOBALS['conf']['container_service_providers']['PantheonServiceProvider'] = '\Pantheon\Internal\PantheonServiceProvider';

/**
 * Trusted host settings.
 *
 * "Trusted host settings" are not necessary on Pantheon; traffic will only
 * be routed to your site if the host settings match a domain configured for
 * your site in the dashboard.
 */
$settings['trusted_host_patterns'][] = '.*';

/**
 * Environment indicator.
 */
if (getenv('PANTHEON_ENVIRONMENT') === 'dev') {
    $config['environment_indicator.indicator']['name'] = 'Development';
    $config['environment_indicator.indicator']['bg_color'] = '#efd01b';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
}
elseif (getenv('PANTHEON_ENVIRONMENT') === 'test') {
    $config['environment_indicator.indicator']['name'] = 'Test';
    $config['environment_indicator.indicator']['bg_color'] = '#d25e0f';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
}
elseif (getenv('PANTHEON_ENVIRONMENT') === 'live') {
    $config['environment_indicator.indicator']['name'] = 'Live';
    $config['environment_indicator.indicator']['bg_color'] = '#efd01b';
    $config['environment_indicator.indicator']['fg_color'] = '#ffffff';
}
else {
    $config['environment_indicator.indicator']['name'] = 'Multidev (' . getenv('PANTHEON_ENVIRONMENT') . ')';
    $config['environment_indicator.indicator']['bg_color'] = '#efd01b';
    $config['environment_indicator.indicator']['fg_color'] = '#000000';
}
