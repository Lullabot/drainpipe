#!/bin/bash

set -eux
echo "Updating..."

# We intentionally do not set up syncing of static assets, as we have
# decided to always use Stage File Proxy:
# https://architecture.lullabot.com/adr/20210729-stage-file-proxy/

# Set up permissions for the files directories.
chgrp -R www-data "${TUGBOAT_ROOT}/web/sites/default/files"
chmod -R g+w "${TUGBOAT_ROOT}/web/sites/default/files"
chmod 2775 "${TUGBOAT_ROOT}/web/sites/default/files"

composer install

# Install Drupal using the standard profile.
vendor/bin/task drupal:install

# To use an existing site, add the appropriate environment variables
# as document at <LINK> and instead run:
# vendor/bin/task <provider>:fetch-db
# vendor/bin/task drupal:import-db

# Install
echo "Install drush"
drush site:install
