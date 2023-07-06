#!/bin/bash

set -eux
echo "Initializing..."

# Install mysql or mariadb client.
apt-get update
apt-get install -y mariadb-client

# Link the document root to the expected path. Tugboat uses /docroot
# by default. So, if Drupal is located at any other path in your git
# repository, change that here. This example links /web to the docroot
ln -snf "${TUGBOAT_ROOT}/web" "${DOCROOT}"

# Install the PHP opcache as it's not included by default and needed for
# decent performance.
docker-php-ext-install opcache

# GD dependencies.
apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev

# WebP dependencies.
apt-get install -y libwebp-dev libwebp6 webp libmagickwand-dev

# Build and install gd.
docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
docker-php-ext-install gd

# Install ImageMagick. This is recommended by both Acquia and Pantheon instead
# of GD. Lullabot will likely be publishing an ADR recommending it too.
apt-get install -y imagemagick
# drainpipe-start
# This is necessary for testing as this repository doesn't hold a Drupal site.
shopt -s dotglob
mkdir ../drainpipe-tmp
mv * ../drainpipe-tmp/
composer create-project drupal/recommended-project .
mv ../drainpipe-tmp/.tugboat .
mkdir drainpipe
mv ../drainpipe-tmp/* ./drainpipe/
composer config extra.drupal-scaffold.gitignore true
composer config --json extra.drupal-scaffold.allowed-packages \[\"lullabot/drainpipe\"]
composer config --no-plugins allow-plugins.composer/installers true
composer config --no-plugins allow-plugins.drupal/core-composer-scaffold true
composer config --no-plugins allow-plugins.lullabot/drainpipe true
composer config repositories.drainpipe --json '{"type": "path", "url": "drainpipe", "options": {"symlink": true}}'
composer config minimum-stability dev
composer require lullabot/drainpipe --with-all-dependencies
# drainpipe-end
ls -lha
ls -lha .tugboat
ls -lha .tugboat/steps
cat ./.tugboat/steps/update.sh
