<?php

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
  $config['environment_indicator.indicator']['bg_color'] = '#990055';
  $config['environment_indicator.indicator']['fg_color'] = '#fff';
}
