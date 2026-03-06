#!/usr/bin/env bash
# deploy.sh — Deploy an Acquia CDE (Cloud Development Environment) for a Bitbucket PR.
#
# Required environment variables:
#   ACQUIA_API_KEY            Acquia API Key
#   ACQUIA_API_SECRET         Acquia API Secret
#   ACQUIA_SSH_PRIVATE_KEY    SSH private key (base64-encoded) with Acquia Git access
#   ACQUIA_APP_UUID           Application UUID from Acquia Cloud console
#   ACQUIA_SITE_GROUP         Application/site group name (e.g. "mysite" from mysite.dev)
#   BITBUCKET_PR_ID           Provided automatically by Bitbucket Pipelines
#   BITBUCKET_COMMIT          Provided automatically by Bitbucket Pipelines
#   BITBUCKET_WORKSPACE       Provided automatically by Bitbucket Pipelines
#   BITBUCKET_REPO_SLUG       Provided automatically by Bitbucket Pipelines
#
# Optional environment variables:
#   ACQUIA_SOURCE_ENVIRONMENT Source environment to copy DB from (default: prod)
#   ACQUIA_REVIEW_RUN_INSTALLER Set to "true" to run site:install instead of DB copy
#   BITBUCKET_USERNAME        Bitbucket username for commit status API
#   BITBUCKET_APP_PASSWORD    Bitbucket App Password for commit status API

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

# ---------------------------------------------------------------------------
# 1. Setup
# ---------------------------------------------------------------------------
drainpipe_setup_tools
drainpipe_setup_ssh
drainpipe_acquia_auth

git config --global user.name "Drainpipe Bot"
git config --global user.email "no-reply@example.com"

# ---------------------------------------------------------------------------
# 2. Build
# ---------------------------------------------------------------------------
composer install --no-interaction --prefer-dist --ignore-platform-reqs
./vendor/bin/task build
./vendor/bin/task snapshot:directory directory=/tmp/release

# ---------------------------------------------------------------------------
# 3. Environment naming
# ---------------------------------------------------------------------------
SOURCE_ENV="${ACQUIA_SOURCE_ENVIRONMENT:-dev}"
ENV_LABEL="PR-${BITBUCKET_PR_ID}"
ENV_NAME="pr-${BITBUCKET_PR_ID}"
BUILD_BRANCH="${ENV_NAME}-build"
CDE_ALIAS="${ACQUIA_SITE_GROUP}.${ENV_NAME}"

# ---------------------------------------------------------------------------
# 4. Create or reuse CDE
# ---------------------------------------------------------------------------
echo "Checking whether CDE '${ENV_LABEL}' already exists..."
EXISTING_ENV=$(acli api:environments:list --no-interaction "${ACQUIA_APP_UUID}" \
  | jq -r --arg label "${ENV_LABEL}" '.[] | select(.label == $label) | .name' || true)

if [ -z "${EXISTING_ENV}" ]; then
  echo "CDE '${ENV_LABEL}' not found — creating..."
  acli env:create "${ENV_LABEL}" "${BUILD_BRANCH}" "${ACQUIA_APP_UUID}" --no-interaction

  # Poll until the environment is provisioned (up to ~2 minutes).
  echo "Waiting for CDE to provision..."
  ATTEMPTS=0
  MAX_ATTEMPTS=12
  while [ "${ATTEMPTS}" -lt "${MAX_ATTEMPTS}" ]; do
    STATUS=$(acli api:environments:list --no-interaction "${ACQUIA_APP_UUID}" \
      | jq -r --arg label "${ENV_LABEL}" '.[] | select(.label == $label) | .status' || true)
    echo "  Status: ${STATUS} (attempt $((ATTEMPTS + 1))/${MAX_ATTEMPTS})"
    if [ "${STATUS}" = "normal" ]; then
      echo "CDE is ready."
      break
    fi
    ATTEMPTS=$((ATTEMPTS + 1))
    sleep 10
  done

  if [ "${ATTEMPTS}" -eq "${MAX_ATTEMPTS}" ]; then
    echo "ERROR: CDE did not become ready within the expected time."
    exit 1
  fi

  # Copy the database from the source environment (unless installer mode is on).
  if [ "${ACQUIA_REVIEW_RUN_INSTALLER:-}" != "true" ]; then
    echo "Copying database from '${SOURCE_ENV}' to '${CDE_ALIAS}'..."
    acli api:environments:database-copy --no-interaction \
      "${ACQUIA_SITE_GROUP}.${SOURCE_ENV}" "${CDE_ALIAS}" "${ACQUIA_SITE_GROUP}"
  fi
else
  echo "CDE '${ENV_LABEL}' already exists — will update."
fi

# ---------------------------------------------------------------------------
# 5. Resolve Acquia Git remote and add to known_hosts
# ---------------------------------------------------------------------------
echo "Resolving Acquia VCS URL..."
ENV_INFO=$(acli api:environments:find --no-interaction "${ACQUIA_SITE_GROUP}.dev")
ACQUIA_GIT_REMOTE=$(echo "${ENV_INFO}" | jq -r '.vcs.url')
REMOTE_HOST=$(echo "${ACQUIA_GIT_REMOTE}" | awk -F'[@:]' '{print $2}')

echo "Adding ${REMOTE_HOST} to known_hosts..."
ssh-keyscan -H "${REMOTE_HOST}" >> ~/.ssh/known_hosts || true

# ---------------------------------------------------------------------------
# 6. Push code to Acquia
# ---------------------------------------------------------------------------
echo "Pushing code to Acquia (branch: ${BUILD_BRANCH})..."
./vendor/bin/task deploy:git \
  directory=/tmp/release \
  branch="${BUILD_BRANCH}" \
  remote="${ACQUIA_GIT_REMOTE}" \
  message="${BITBUCKET_COMMIT}"

# ---------------------------------------------------------------------------
# 7. Wait for Acquia to sync the new code
# ---------------------------------------------------------------------------
echo "Switching code on '${CDE_ALIAS}' to branch '${BUILD_BRANCH}'..."
echo "This may take a while..."
acli api:environments:code-switch --task-wait --no-interaction "${CDE_ALIAS}" "${BUILD_BRANCH}"
echo "Code switch complete."

# ---------------------------------------------------------------------------
# 8. Download Drush aliases and run site updates
# ---------------------------------------------------------------------------
acli remote:aliases:download --no-interaction "${ACQUIA_SITE_GROUP}"

# Fix drush9 paths issue (see acquia/update action).
yq eval '(.** | .paths) = {}' -i "drush/sites/${ACQUIA_SITE_GROUP}.site.yml"

if [ "${ACQUIA_REVIEW_RUN_INSTALLER:-}" = "true" ]; then
  echo "Running site installer..."
  ./vendor/bin/drush "@${CDE_ALIAS}" --yes site:install --existing-config
elif ./vendor/bin/task -l 2>/dev/null | grep -q '^\* update: '; then
  echo "Running task update..."
  ./vendor/bin/task update "site=@${CDE_ALIAS}"
else
  echo "Running task drupal:update..."
  ./vendor/bin/task drupal:update "site=@${CDE_ALIAS}"
fi

# ---------------------------------------------------------------------------
# 9. Post commit status with environment URL
# ---------------------------------------------------------------------------
echo "Retrieving environment URL..."
CDE_INFO=$(acli api:environments:find --no-interaction "${CDE_ALIAS}")
ENV_DOMAIN=$(echo "${CDE_INFO}" | jq -r '.domains[0]')
ENV_URL="https://${ENV_DOMAIN}"

echo "Environment URL: ${ENV_URL}"
drainpipe_post_commit_status "SUCCESSFUL" "${ENV_URL}" "Acquia CDE for PR #${BITBUCKET_PR_ID}"
echo "Deployment complete."
