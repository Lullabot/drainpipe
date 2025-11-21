#!/bin/bash
set -eux

TESTMODE=${1:-new}
if [ "$TESTMODE" != "new" ] && [ "$TESTMODE" != "update" ]; then
    echo "Error: Invalid parameter '$TESTMODE'. Use 'new' or 'update'"
    exit 1
fi

# Temporary directory for testing
drainpipedir=$(realpath "$(mktemp -d "/tmp/drainpipe-XXXXXXXXX")")
function tmpdir_cleanup() {
  cd $drainpipedir && ddev delete -yO && cd ~
  rm -rf "$drainpipedir"
}
trap "tmpdir_cleanup" SIGINT SIGTERM EXIT

# Start new Drupal project and copy Drainpipe as subdirectory
composer create-project drupal/recommended-project $drainpipedir --ignore-platform-reqs
cp -R . $drainpipedir/drainpipe
cp -R ./metapackages $drainpipedir/metapackages
cd $drainpipedir

# Configure DDEV settings for Drainpipe
ddev config --auto
ddev start
ddev composer config extra.drupal-scaffold.gitignore true
ddev composer config --json extra.drupal-scaffold.allowed-packages '["lullabot/drainpipe-dev", "lullabot/drainpipe"]'
ddev composer config --no-plugins allow-plugins.composer/installers true
ddev composer config --no-plugins allow-plugins.drupal/core-composer-scaffold true
ddev composer config --no-plugins allow-plugins.lullabot/drainpipe true
ddev composer config --no-plugins allow-plugins.lullabot/drainpipe-dev true
ddev composer config extra.drainpipe.acquia --json '{"settings": true, "github": []}'

# Install current Drainpipe version
if [ "$TESTMODE" == "update" ]; then
  ddev composer require "lullabot/drainpipe" --with-all-dependencies
  ddev composer require "lullabot/drainpipe-dev" --dev --with-all-dependencies
fi

# Install Drainpipe and DrainpipeDev from subdirectory
ddev composer config minimum-stability dev
ddev composer config repositories.drainpipe --json '{"type": "path", "url": "drainpipe", "options": {"symlink": false}}'
ddev composer config repositories.drainpipe-dev --json '{"type": "path", "url": "drainpipe/drainpipe-dev", "options": {"symlink": false}}'
ddev composer require "lullabot/drainpipe @dev" --with-all-dependencies
ddev composer require "lullabot/drainpipe-dev @dev" --dev --with-all-dependencies
rm -rf drainpipe

# Install NodeJS version from .nvmrc
ddev config --nodejs-version "auto"
ddev restart

# Install Yarn
ddev yarn set version berry
ddev yarn init -y
ddev yarn cache clear
ddev restart

# Install Drupal site
ddev drush --yes site:install
ddev drush config:export --yes

# Install metapackages
ddev yarn add drainpipe-javascript@file:./metapackages/javascript/
ddev yarn add drainpipe-sass@file:./metapackages/sass/

# Verify Taskfile version
INSTALLED=$(ddev task --version)
EXPECTED=$(cat vendor/lullabot/drainpipe/.taskfile | tr -d 'v')
if [[ ${INSTALLED} -ne ${EXPECTED} ]]; then
  echo "Taskfile version does not match: expected ${EXPECTED}, got ${INSTALLED}"
  exit 1
fi

# Verify NodeJS version
INSTALLED=$(ddev exec "node -v | cut -d'.' -f1 | tr -d 'v'")
EXPECTED=$(cat .nvmrc)
if [[ ${INSTALLED} -ne ${EXPECTED} ]]; then
  echo "NodeJS version does not match: expected ${EXPECTED}, got ${INSTALLED}"
  exit 1
fi

# Execute tests to confirm they are able to run
set +e
ddev task test:autofix
ddev task test:lint
ddev task test:phpcs
ddev task test:phpstan
ddev task test:phpunit
ddev task test:config
ddev task test:security
ddev task test:untracked
set -e
