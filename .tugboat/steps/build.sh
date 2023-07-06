#!/bin/bash

set -eux
# drainpipe-start
# This is necessary for testing as this repository doesn't hold a Drupal site.
mkdir drainpipe
mv * drainpipe/ 2>/dev/null
composer create-project drupal/recommended-project .
composer config extra.drupal-scaffold.gitignore true
composer config --json extra.drupal-scaffold.allowed-packages \[\"lullabot/drainpipe\"]
composer config --no-plugins allow-plugins.composer/installers true
composer config --no-plugins allow-plugins.drupal/core-composer-scaffold true
composer config --no-plugins allow-plugins.lullabot/drainpipe true
composer config repositories.drainpipe --json '{"type": "path", "url": "drainpipe", "options": {"symlink": false}}'
composer config minimum-stability dev
composer require lullabot/drainpipe --with-all-dependencies
# drainpipe-end
composer install
./vendor/bin/task sync
./vendor/bin/task build
./vendor/bin/task drupal:update
