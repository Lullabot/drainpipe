#!/bin/bash
set -eux
# Helper script to setup a local test of Drainpipe
# Copy this to the same directory level that your drainpipe directory is in
composer create-project drupal/recommended-project drainpipe-test --ignore-platform-req=ext-gd
cd drainpipe-test
cp -R ../drainpipe .

ddev config --auto
ddev config --nodejs-version "21"
ddev start
ddev composer config extra.drupal-scaffold.gitignore true
ddev composer config --json extra.drupal-scaffold.allowed-packages '["lullabot/drainpipe-dev", "lullabot/drainpipe"]'
ddev composer config --no-plugins allow-plugins.composer/installers true
ddev composer config --no-plugins allow-plugins.drupal/core-composer-scaffold true
ddev composer config --no-plugins allow-plugins.lullabot/drainpipe true
ddev composer config --no-plugins allow-plugins.lullabot/drainpipe-dev true
ddev composer config repositories.drainpipe --json '{"type": "path", "url": "drainpipe", "options": {"symlink": false}}'
ddev composer config repositories.drainpipe-dev --json '{"type": "path", "url": "drainpipe/drainpipe-dev", "options": {"symlink": false}}'
ddev composer config minimum-stability dev
ddev composer require "lullabot/drainpipe @dev" --with-all-dependencies
ddev composer require "lullabot/drainpipe-dev @dev" --dev --with-all-dependencies
rm -rf drainpipe

echo "hooks:" >> .ddev/config.yaml
echo "  post-start:" >> .ddev/config.yaml
echo "    - exec: mysql -uroot -proot -hdb -e \"CREATE DATABASE IF NOT EXISTS firefox; GRANT ALL ON firefox.* TO 'db'@'%';\"" >> .ddev/config.yaml
echo "    - exec: mysql -uroot -proot -hdb -e \"CREATE DATABASE IF NOT EXISTS chrome; GRANT ALL ON chrome.* TO 'db'@'%';\"" >> .ddev/config.yaml
ddev config --web-environment="NIGHTWATCH_DRUPAL_URL_FIREFOX=https://drupal_firefox,NIGHTWATCH_DRUPAL_URL_CHROME=https://drupal_chrome"
ddev config --additional-hostnames="*.drainpipe"
ddev restart

ddev yarn set version berry
ddev yarn init -y
ddev yarn cache clear
echo "packageExtensions:" >> .yarnrc.yml
echo '  "nightwatch@*":' >> .yarnrc.yml
echo '    dependencies:' >> .yarnrc.yml
echo '      ws: "*"' >> .yarnrc.yml
ddev yarn add nightwatch nightwatch-axe-verbose @lullabot/nightwatch-drupal-commands --dev
yarn

ddev drush --yes site:install
ddev drush --uri=https://drupal_firefox --yes site:install
ddev drush --uri=https://drupal_chrome --yes site:install
ddev drush config:export --yes

yarn add drainpipe-javascript@file:../drainpipe/metapackages/javascript/
yarn add drainpipe-sass@file:../drainpipe/metapackages/sass/
