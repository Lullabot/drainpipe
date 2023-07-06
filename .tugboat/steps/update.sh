#!/bin/bash

set -eux

# drainpipe-start
./vendor/bin/drush config:export --yes
# drainpipe-end
./vendor/bin/task sync
