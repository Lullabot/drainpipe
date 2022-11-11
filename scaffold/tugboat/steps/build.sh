#!/bin/bash

set -eux
vendor/bin/task drupal:update
vendor/bin/drush user:login
