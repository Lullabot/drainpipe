#!/bin/bash

set -eux
echo "Updating..."

./vendor/bin/task sync
# drainpipe-start
./vendor/bin/drush config:export --yes
# drainpipe-end
