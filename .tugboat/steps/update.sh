#!/bin/bash

set -eux

./vendor/bin/task sync
# drainpipe-start
./vendor/bin/drush config:export --yes
# drainpipe-end
