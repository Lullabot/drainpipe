#!/bin/bash

set -eux
echo "Initializing..."
# Install prerequisite packages
apt-get update
apt-get install -y mariadb-client libldap2-dev

# Install drush-launcher
wget -O /usr/local/bin/drush https://github.com/drush-ops/drush-launcher/releases/download/0.10.1/drush.phar
chmod +x /usr/local/bin/drush

# Link the document root to the expected path. Tugboat uses /docroot
# by default. So, if Drupal is located at any other path in your git
# repository, change that here. This example links /web to the docroot
[ -d "${DOCROOT}" ] || ln -snf "${TUGBOAT_ROOT}/web" "${DOCROOT}"

docker-php-ext-install opcache
a2enmod headers rewrite

docker-php-ext-install memcache

# WebP
apt-get install -y libwebp-dev libwebp6 webp libmagickwand-dev
docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
docker-php-ext-install gd

# Configure GD
apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev
docker-php-ext-configure gd --with-jpeg --with-freetype && docker-php-ext-install gd

# Install drush-launcher, if desired.
wget -O /usr/local/bin/drush https://github.com/drush-ops/drush-launcher/releases/download/0.6.0/drush.phar
chmod +x /usr/local/bin/drush