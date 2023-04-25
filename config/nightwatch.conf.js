let a11yPath, drupalCommandsPath;
let yarn2 = false;
try {
  const { resolveToUnqualified } = require('pnpapi');
  a11yPath = resolveToUnqualified('nightwatch-accessibility', __filename).replace(/\/$/, '');
  drupalCommandsPath = resolveToUnqualified('@lullabot/nightwatch-drupal-commands', __filename).replace(/\/$/, '');
  yarn2 = true;
} catch(e) {
  a11yPath = './node_modules/nightwatch-accessibility';
  drupalCommandsPath = './node_modules/@lullabot/nightwatch-drupal-commands';
}


module.exports = {
  // An array of folders (excluding subfolders) where your tests are located;
  // if this is not specified, the test source must be passed as the second argument to the test runner.
  src_folders: ['test'],

  output_folder: 'test_result',

  // See https://nightwatchjs.org/guide/working-with-page-objects/
  page_objects_path: [`${drupalCommandsPath}/page_objects`],

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
      "screenshots" : {
        "enabled" : true,
        "on_failure" : true,
        "on_error" : false,
        "path" : "test_result"
      }
    },
    firefox: {
      desiredCapabilities: {
        resolution: "1240x4000",
        browserName: 'firefox',
        acceptInsecureCerts: true,
        alwaysMatch: {
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
      },
      globals: {
        drupalUrl: process.env.NIGHTWATCH_DRUPAL_URL_FIREFOX && process.env.NIGHTWATCH_DRUPAL_URL_FIREFOX.length ? process.env.NIGHTWATCH_DRUPAL_URL_FIREFOX.replace(/\/$/, '') : process.env.NIGHTWATCH_DRUPAL_URL,
      },
    },
    chrome: {
      desiredCapabilities: {
        resolution: "1240x4000",
        browserName: 'chrome',
        'goog:chromeOptions': {
          // More info on Chromedriver: https://sites.google.com/a/chromium.org/chromedriver/
          //
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
      },
      globals: {
        drupalUrl: process.env.NIGHTWATCH_DRUPAL_URL_CHROME && process.env.NIGHTWATCH_DRUPAL_URL_CHROME.length ? process.env.NIGHTWATCH_DRUPAL_URL_CHROME.replace(/\/$/, '') : process.env.NIGHTWATCH_DRUPAL_URL,
      },
    },
  }
};
