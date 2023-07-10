#!/bin/bash

set -eux

./vendor/bin/task build
./vendor/bin/task drupal:update
