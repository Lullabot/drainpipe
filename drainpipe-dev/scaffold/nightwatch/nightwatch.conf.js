const drainpipeConfig = require('./vendor/lullabot/drainpipe-dev/config/nightwatch.conf.js');

module.exports = {
  ...drainpipeConfig,
  // See https://nightwatchjs.org/gettingstarted/configuration/
  output_folder: 'test_result',
};
