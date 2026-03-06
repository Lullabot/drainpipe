#!/usr/bin/env bash
# common.sh — Shared helper functions for Drainpipe Bitbucket pipelines.
# Source this file; do not execute it directly.

set -euo pipefail

# Install required CLI tools if not already present.
drainpipe_setup_tools() {
  apt-get update -qq
  apt-get install -y -qq git curl jq unzip

  # Composer
  if ! command -v composer &>/dev/null; then
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  fi
  export COMPOSER_ALLOW_SUPERUSER=1

  # Task (Taskfile runner)
  if ! command -v task &>/dev/null; then
    sh -c "$(curl -sSL https://taskfile.dev/install.sh)" -- -d -b /usr/local/bin
  fi

  # yq
  if ! command -v yq &>/dev/null; then
    YQ_VERSION="v4.52.4"
    curl -sSL "https://github.com/mikefarah/yq/releases/download/${YQ_VERSION}/yq_linux_amd64" \
      -o /usr/local/bin/yq
    chmod +x /usr/local/bin/yq
  fi

  # acli (Acquia CLI)
  if ! command -v acli &>/dev/null; then
    curl -sSL "https://github.com/acquia/cli/releases/latest/download/acli.phar" \
      -o /usr/local/bin/acli
    chmod +x /usr/local/bin/acli
  fi
}

# Write the Acquia SSH private key and configure SSH.
# Expects ACQUIA_SSH_PRIVATE_KEY to be set (base64-encoded).
drainpipe_setup_ssh() {
  mkdir -p ~/.ssh
  chmod 700 ~/.ssh
  echo "${ACQUIA_SSH_PRIVATE_KEY}" | base64 -d > ~/.ssh/id_rsa
  chmod 600 ~/.ssh/id_rsa
  echo "StrictHostKeyChecking no" >> ~/.ssh/config
}

# Authenticate with Acquia CLI.
# Expects ACQUIA_API_KEY and ACQUIA_API_SECRET to be set.
drainpipe_acquia_auth() {
  acli auth:login \
    --key="${ACQUIA_API_KEY}" \
    --secret="${ACQUIA_API_SECRET}" \
    --no-interaction
}

# Post a commit build status to Bitbucket.
#
# Usage: drainpipe_post_commit_status STATE URL DESCRIPTION
#   STATE       — INPROGRESS | SUCCESSFUL | FAILED
#   URL         — Link to the environment (may be empty)
#   DESCRIPTION — Short human-readable description
#
# Requires BITBUCKET_USERNAME, BITBUCKET_APP_PASSWORD,
# BITBUCKET_WORKSPACE, BITBUCKET_REPO_SLUG, and BITBUCKET_COMMIT.
# No-ops gracefully when credentials are absent.
drainpipe_post_commit_status() {
  local STATE="${1}"
  local URL="${2}"
  local DESCRIPTION="${3}"

  if [ -z "${BITBUCKET_USERNAME:-}" ] || [ -z "${BITBUCKET_APP_PASSWORD:-}" ]; then
    echo "BITBUCKET_USERNAME or BITBUCKET_APP_PASSWORD not set — skipping commit status post."
    return 0
  fi

  local API_URL="https://api.bitbucket.org/2.0/repositories/${BITBUCKET_WORKSPACE}/${BITBUCKET_REPO_SLUG}/commit/${BITBUCKET_COMMIT}/statuses/build"

  local PAYLOAD
  PAYLOAD=$(jq -n \
    --arg state       "${STATE}" \
    --arg url         "${URL}" \
    --arg description "${DESCRIPTION}" \
    '{state: $state, key: "drainpipe-acquia-review-app", name: "Acquia Review App", url: $url, description: $description}')

  curl -s -f -X POST \
    -u "${BITBUCKET_USERNAME}:${BITBUCKET_APP_PASSWORD}" \
    -H "Content-Type: application/json" \
    --data "${PAYLOAD}" \
    "${API_URL}" || echo "Warning: failed to post commit status to Bitbucket."
}
