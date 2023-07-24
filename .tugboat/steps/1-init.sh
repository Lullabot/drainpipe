#!/bin/bash

set -eux
echo "Initializing..."

# Install task
sh -c "$(curl --location https://raw.githubusercontent.com/go-task/task/v3.24.0/install-task.sh)" -- -d -b /usr/local/bin

# Install mysql or mariadb client.
apt-get update
apt-get install -y mariadb-client

# Link the document root to the expected path. Tugboat uses /docroot
# by default. So, if Drupal is located at any other path in your git
# repository, change that here. This example links /web to the docroot
ln -snf "${TUGBOAT_ROOT}/web" "${DOCROOT}"

# Create the Drupal private and public files directories if they aren't
# already present.
mkdir -p "${TUGBOAT_ROOT}/web/sites/default/files"
chmod 777 "${TUGBOAT_ROOT}/web/sites/default/files"

# Install the PHP opcache as it's not included by default and needed for
# decent performance.
docker-php-ext-install opcache

# GD dependencies.
apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev

# WebP dependencies.
apt-get install -y libwebp-dev libwebp7 webp libmagickwand-dev

# Build and install gd.
docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
docker-php-ext-install gd

# Install ImageMagick. This is recommended by both Acquia and Pantheon instead
# of GD. Lullabot will likely be publishing an ADR recommending it too.
apt-get install -y imagemagick

# Install node
curl -fsSL https://deb.nodesource.com/setup_current.x | bash -
apt-get install -y nodejs
npm install -g n
n 18
# This only works for node > 16, but that version is unsupported now anyway.
corepack enable
#drainpipe-start
# This is necessary for testing as this repository doesn't hold a Drupal site.
shopt -s dotglob
mkdir ../drainpipe-tmp
mv * ../drainpipe-tmp/
composer create-project drupal/recommended-project .
mv ../drainpipe-tmp drainpipe
composer config extra.drupal-scaffold.gitignore true
composer config --json extra.drupal-scaffold.allowed-packages \[\"lullabot/drainpipe\"]
composer config --no-plugins allow-plugins.composer/installers true
composer config --no-plugins allow-plugins.drupal/core-composer-scaffold true
composer config --no-plugins allow-plugins.lullabot/drainpipe true
composer config repositories.drainpipe --json '{"type": "path", "url": "drainpipe", "options": {"symlink": true}}'
composer config extra.drainpipe --json '{"tugboat": {}}'
composer config minimum-stability dev
composer require lullabot/drainpipe --with-all-dependencies
cp web/sites/default/default.settings.php web/sites/default/settings.php
#drainpipe-end

composer install
#drainpipe-start
rm -rf .tugboat
mv drainpipe/.tugboat .tugboat
#drainpipe-end
