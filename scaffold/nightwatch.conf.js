const { resolveToUnqualified } = require('pnpapi');
const a11yPath = resolveToUnqualified('nightwatch-accessibility', __filename).replace(/\/$/, '');
const drupalCommandsPath = resolveToUnqualified('@lullabot/nightwatch-drupal-commands', __filename).replace(/\/$/, '');

module.exports = {
  // An array of folders (excluding subfolders) where your tests are located;
  // if this is not specified, the test source must be passed as the second argument to the test runner.
  src_folders: ['test'],

  // See https://nightwatchjs.org/guide/working-with-page-objects/
  page_objects_path: '',

  // See https://nightwatchjs.org/guide/extending-nightwatch/#writing-custom-commands
  custom_commands_path:  [`${a11yPath}/commands`, `${drupalCommandsPath}/commands`],

  // See https://nightwatchjs.org/guide/extending-nightwatch/#writing-custom-assertions
  custom_assertions_path: [`${a11yPath}/assertions`],

  // See https://nightwatchjs.org/guide/#external-globals
  globals_path : '',

  webdriver: {},

  test_settings: {
    default: {
      filter: '**/*.nightwatch.js',
    },
    firefox: {
      desiredCapabilities: {
        browserName: 'firefox',
        alwaysMatch: {
          acceptInsecureCerts: true,
          'moz:firefoxOptions': {
            args: [
              //'-headless',
              // '-verbose'
            ]
          }
        }

      },
      webdriver: {
        start_process: false,
        host: 'firefox',
        port: 4444,
        cli_args: [
          // very verbose geckodriver logs
          // '-vv'
        ]
      }
    },
    chrome: {
      desiredCapabilities: {
        browserName: 'chrome',
        'goog:chromeOptions': {
          // More info on Chromedriver: https://sites.google.com/a/chromium.org/chromedriver/
          //
          // This tells Chromedriver to run using the legacy JSONWire protocol (not required in Chrome 78)
          w3c: false,
          args: [
            '--no-sandbox',
            '--ignore-certificate-errors',
            '--allow-insecure-localhost',
            //'--headless'
          ]
        }
      },
      webdriver: {
        start_process: false,
        host: 'chrome',
        port: 4444,
        cli_args: [
          // --verbose
        ]
      }
    },
  }
};
