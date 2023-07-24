#!/bin/bash

set -eux
echo "Updating..."

#drainpipe-start
mv .tugboat .tugboat-tmp
#drainpipe-end
composer install
./vendor/bin/task sync
#drainpipe-start
./vendor/bin/drush config:export --yes
rm -rf .tugboat
mv .tugboat-tmp .tugboat
#drainpipe-end
