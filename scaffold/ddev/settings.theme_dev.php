<?php

declare(strict_types=1);

/**
 * @file
 * Theme development mode settings.
 *
 * Set DRAINPIPE_THEME_DEV_MODE=1 in .env to enable theme development mode.
 * By default, debugging is off and caches are on.
 */

if (getenv('DRAINPIPE_THEME_DEV_MODE')) {
  // Enable Twig debug output, disable Twig caching, and enable auto-reload so
  // template changes are picked up without a cache rebuild. This also enables
  // stepping through Twig templates in IDEs via Xdebug.
  $config['twig.settings']['debug'] = TRUE;
  $config['twig.settings']['auto_reload'] = TRUE;
  $config['twig.settings']['cache'] = FALSE;

  // Replace render, page, and dynamic page caches with the null backend so
  // theme changes are visible immediately without running cache:rebuild.
  $settings['cache']['bins']['render'] = 'cache.backend.null';
  $settings['cache']['bins']['page'] = 'cache.backend.null';
  $settings['cache']['bins']['dynamic_page_cache'] = 'cache.backend.null';

  // Expose cache tags in HTTP response headers for debugging.
  $settings['http.response.debug_cacheability_headers'] = TRUE;
}
