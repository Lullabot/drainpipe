#!/usr/bin/env bash
#
# Runs Prettier, Stylelint and (when checking) ESLint in each given front-end
# directory. Called by the test:lint and test:autofix tasks.
#
# Usage: frontend-lint.sh <check|fix> <dir> [<dir>...]
#
# Each tool runs from inside the directory, so config files are resolved
# relative to it. A tool is skipped, with a message, when its config file is
# not present. The package manager is detected from a yarn.lock or
# package-lock.json in the directory or any parent up to the current directory
# (the project root), so themes that are workspaces of a root package.json work.
#
# Binaries are invoked with `yarn exec --` / `npm exec --no --` rather than
# `yarn <bin>` / `npm run <bin>`, so a package.json script with the same name
# as the tool (e.g. "prettier": "prettier -w .") can never shadow it, and flags
# such as --check reach the tool rather than npm. The `--` after `yarn exec` is
# required on Yarn 1, which otherwise drops the flags. `--no` stops npm from
# downloading a tool that is not installed.
#
# Every tool runs even if an earlier one fails; the exit code is 1 if any failed.

set -uo pipefail

MODE="${1:-}"
if [ "$MODE" != "check" ] && [ "$MODE" != "fix" ] || [ $# -lt 2 ]; then
  echo "Usage: $0 <check|fix> <dir> [<dir>...]" >&2
  exit 2
fi
shift

ROOT="$(pwd)"
FAILED=0

# Prints the package manager command for $1, or nothing if no lockfile is found.
detect_pm() {
  local search
  search="$(cd "$1" && pwd)"
  while :; do
    if [ -f "$search/yarn.lock" ]; then
      echo "yarn exec --"
      return
    elif [ -f "$search/package-lock.json" ]; then
      echo "npm exec --no --"
      return
    fi
    if [ "$search" = "$ROOT" ] || [ "$search" = "/" ]; then
      return
    fi
    search="$(dirname "$search")"
  done
}

# run_tool <name> <config description> <config files...> -- <command...>
# Runs the command in $DIR with the package manager prefix if any of the config
# files exists there, otherwise prints why it was skipped.
run_tool() {
  local name="$1" config_desc="$2" found=0
  shift 2
  while [ "$1" != "--" ]; do
    if [ -f "$DIR/$1" ]; then
      found=1
    fi
    shift
  done
  shift

  if [ "$found" -eq 0 ]; then
    echo "Skipping $name in '$DIR': no $config_desc found."
  elif [ ${#PM[@]} -eq 0 ]; then
    echo "Skipping $name in '$DIR': $config_desc found but no yarn.lock or package-lock.json."
  else
    echo "Running $name in '$DIR'"
    if ! (cd "$DIR" && "${PM[@]}" "$@"); then
      FAILED=1
    fi
  fi
}

for DIR in "$@"; do
  if [ ! -d "$DIR" ]; then
    echo "Front-end directory '$DIR' does not exist (check FRONTEND_DIRS)."
    FAILED=1
    continue
  fi

  read -r -a PM <<< "$(detect_pm "$DIR")"

  if [ "$MODE" = "fix" ]; then
    run_tool Prettier .prettierrc.json .prettierrc.json -- \
      prettier --write --cache-location=.prettiercache .
    run_tool Stylelint .stylelintrc.json .stylelintrc.json -- \
      stylelint "**/*.{css,scss,sass}" --fix
  else
    run_tool Prettier .prettierrc.json .prettierrc.json -- \
      prettier --check --cache-location=.prettiercache .
    run_tool Stylelint .stylelintrc.json .stylelintrc.json -- \
      stylelint "**/*.{css,scss,sass}"
    run_tool ESLint "ESLint config" \
      eslint.config.js eslint.config.mjs eslint.config.cjs \
      eslint.config.ts eslint.config.mts eslint.config.cts \
      .eslintrc.json .eslintrc.js .eslintrc.cjs .eslintrc.yml .eslintrc.yaml -- \
      eslint .
  fi
done

exit "$FAILED"
