#!/bin/bash

check_composer_install_contents() {
  local string_to_check="$1"
  local expected="$2"
  local file="composer-install-output.txt"

  ddev composer install > $file 2>&1

  # Search for the string in the file
  if grep -q "$string_to_check" "$file"; then
      # String is found
      if [[ "$expected" -eq 0 ]]; then
          echo "Error: String '$string_to_check' found, but it was not expected."
          exit 1
      fi
  else
      # String is not found
      if [[ "$expected" -eq 1 ]]; then
          echo "Error: String '$string_to_check' not found, but it was expected."
          exit 1
      fi
  fi
}

# Exit on patch failure.
ddev composer config --unset extra.composer-exit-on-patch-failure
check_composer_install_contents "Break Composer install if patches don't apply" 1

ddev composer config extra.composer-exit-on-patch-failure --json false
check_composer_install_contents "Break Composer install if patches don't apply" 1

## Opt out.
ddev composer config extra.drainpipe.composer --json '{"disable-exit-on-patch-failure-check": true}'
check_composer_install_contents "Break Composer install if patches don't apply" 0
ddev composer config --unset extra.drainpipe.composer

ddev composer config extra.composer-exit-on-patch-failure --json true
check_composer_install_contents "Break Composer install if patches don't apply" 0

# Drupal core patches level.
ddev composer config extra.patchLevel --json '{"drupal/core": "-p2"}'
check_composer_install_contents "Configure Composer patches to use \`-p2\` as \`patchLevel\` for Drupal core" 0

ddev composer config --unset extra.patchLevel
check_composer_install_contents "Configure Composer patches to use \`-p2\` as \`patchLevel\` for Drupal core" 1

## Opt out.
ddev composer config extra.drainpipe.composer --json '{"disable-drupal-core-patches-level-check": true}'
check_composer_install_contents "Configure Composer patches to use \`-p2\` as \`patchLevel\` for Drupal core" 0
ddev composer config --unset extra.drainpipe.composer

# Patches configuration in composer.json
ddev composer config extra.patches-file "composer.patches.json"
check_composer_install_contents "Store Composer patches configuration in \`composer.json\`" 1

## Opt out.
ddev composer config extra.drainpipe.composer --json '{"disable-drupal-core-patches-level-check": true}'
check_composer_install_contents "Configure Composer patches to use \`-p2\` as \`patchLevel\` for Drupal core" 0
ddev composer config --unset extra.drainpipe.composer

ddev composer config --unset extra.patches-file
check_composer_install_contents "Store Composer patches configuration in \`composer.json\`" 0

# Avoid remote patches
ddev composer config extra.patches --json '{"drupal":{"issue-x":"http"}}'
check_composer_install_contents "Use local copies of patch files." 1

## Opt out: disable-local-patches-check
ddev composer config extra.drainpipe.composer --json '{"disable-local-patches-check": true}'
check_composer_install_contents "Use local copies of patch files." 0
ddev composer config --unset extra.drainpipe.composer

ddev composer config extra.patches --json '{"drupal":{"issue-x":"local-path"}}'
check_composer_install_contents "Use local copies of patch files." 0
