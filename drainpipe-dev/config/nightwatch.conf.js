let a11yPath, drupalCommandsPath;
let yarn2 = false;
try {
  const { resolveToUnqualified } = require('pnpapi');
  a11yPath = resolveToUnqualified('nightwatch-accessibility', __filename).replace(/\/$/, '');
  drupalCommandsPath = resolveToUnqualified('@lullabot/nightwatch-drupal-commands', __filename).replace(/\/$/, '');
  yarn2 = true;
} catch(e) {
  a11yPath = './node_modules/nightwatch-accessibility/nightwatch';
  drupalCommandsPath = './node_modules/@lullabot/nightwatch-drupal-commands';
}

const firefoxLaunchUrl = process.env.NIGHTWATCH_DRUPAL_URL_FIREFOX && process.env.NIGHTWATCH_DRUPAL_URL_FIREFOX.length ? process.env.NIGHTWATCH_DRUPAL_URL_FIREFOX.replace(/\/$/, '') : process.env.NIGHTWATCH_DRUPAL_URL;
const chromeLaunchUrl = process.env.NIGHTWATCH_DRUPAL_URL_CHROME && process.env.NIGHTWATCH_DRUPAL_URL_CHROME.length ? process.env.NIGHTWATCH_DRUPAL_URL_CHROME.replace(/\/$/, '') : process.env.NIGHTWATCH_DRUPAL_URL;

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

  test_workers: {
    enabled: false
  },

  test_settings: {
    default: {
      filter: '**/*.nightwatch.js',
      disable_error_log: false,
      screenshots: {
        enabled: false,
        on_failure: true,
        on_error: false,
        path: 'test_result',
      },
    },

    chrome: {
      selenium: {
        start_process: false,
        host: 'chrome',
        port: 4444
      },
      desiredCapabilities: {
        browserName: 'chrome',
        esolution: "1240x4000",
        'goog:chromeOptions': {
          w3c: true,
          args: [
            '--no-sandbox',
            '--ignore-certificate-errors',
            '--allow-insecure-localhost',
            //'--headless'
          ]
        }
      },
      globals: {
        drupalUrl: chromeLaunchUrl,
      },
      launch_url: chromeLaunchUrl,
    },

    firefox: {
      selenium: {
        start_process: false,
        host: 'firefox',
        port: 4444
      },
      desiredCapabilities: {
        browserName: 'firefox',
        resolution: "1240x4000",
        acceptInsecureCerts: true,
        'moz:firefoxOptions': {
          args: [
            // '-headless',
            // '-verbose'
          ]
        }
      },
      globals: {
        drupalUrl: firefoxLaunchUrl,
      },
      launch_url: firefoxLaunchUrl,
    },
  }
};
