#!/bin/bash

set -eux
echo "Updating..."

composer install
./vendor/bin task drupal:update
