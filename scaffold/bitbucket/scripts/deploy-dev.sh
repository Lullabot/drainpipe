#!/usr/bin/env bash
# deploy-dev.sh — Deploy to the Acquia dev environment on merge to main.
#
# Required environment variables:
#   ACQUIA_API_KEY            Acquia API Key
#   ACQUIA_API_SECRET         Acquia API Secret
#   ACQUIA_SSH_PRIVATE_KEY    SSH private key (base64-encoded) with Acquia Git access
#   ACQUIA_SITE_GROUP         Application/site group name (e.g. "mysite" from mysite.dev)
#   BITBUCKET_COMMIT          Provided automatically by Bitbucket Pipelines

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
composer install --no-interaction --prefer-dist

if ./vendor/bin/task -l 2>/dev/null | grep -q '^\* acquia:deploy:before: '; then
  ./vendor/bin/task acquia:deploy:before
fi

./vendor/bin/task snapshot:directory directory=/tmp/release

# ---------------------------------------------------------------------------
# 3. Resolve Acquia dev environment VCS info
# ---------------------------------------------------------------------------
ENV_ALIAS="${ACQUIA_SITE_GROUP}.dev"

echo "Resolving Acquia VCS URL for '${ENV_ALIAS}'..."
ENV_INFO=$(acli api:environments:find --no-interaction "${ENV_ALIAS}")
BRANCH=$(echo "${ENV_INFO}" | jq -r '.vcs.path')
ACQUIA_GIT_REMOTE=$(echo "${ENV_INFO}" | jq -r '.vcs.url')
REMOTE_HOST=$(echo "${ACQUIA_GIT_REMOTE}" | awk -F'[@:]' '{print $2}')

echo "Branch: ${BRANCH}"
echo "Remote: ${ACQUIA_GIT_REMOTE}"

# ---------------------------------------------------------------------------
# 4. Add Acquia Git host to known_hosts
# ---------------------------------------------------------------------------
echo "Adding ${REMOTE_HOST} to known_hosts..."
ssh-keyscan -H "${REMOTE_HOST}" >> ~/.ssh/known_hosts || true

# ---------------------------------------------------------------------------
# 5. Push code to Acquia
# ---------------------------------------------------------------------------
echo "Pushing code to Acquia (branch: ${BRANCH})..."
./vendor/bin/task deploy:git \
  directory=/tmp/release \
  branch="${BRANCH}" \
  remote="${ACQUIA_GIT_REMOTE}" \
  message="${BITBUCKET_COMMIT}"

# ---------------------------------------------------------------------------
# 6. Switch code on dev and wait
# ---------------------------------------------------------------------------
echo "Switching code on '${ENV_ALIAS}' to branch '${BRANCH}'..."
acli api:environments:code-switch --task-wait --no-interaction "${ENV_ALIAS}" "${BRANCH}"
echo "Code switch complete."

# ---------------------------------------------------------------------------
# 7. Download Drush aliases and fix drush9 paths issue
# ---------------------------------------------------------------------------
acli remote:aliases:download --no-interaction "${ACQUIA_SITE_GROUP}"
yq eval '(.** | .paths) = {}' -i "drush/sites/${ACQUIA_SITE_GROUP}.site.yml"

# ---------------------------------------------------------------------------
# 8. Post-deploy hook (if defined)
# ---------------------------------------------------------------------------
if ./vendor/bin/task -l 2>/dev/null | grep -q '^\* acquia:deploy:after: '; then
  echo "Running task acquia:deploy:after..."
  ./vendor/bin/task acquia:deploy:after "site=@${ENV_ALIAS}"
fi

# ---------------------------------------------------------------------------
# 9. Run site updates
# ---------------------------------------------------------------------------
if ./vendor/bin/task -l 2>/dev/null | grep -q '^\* update: '; then
  echo "Running task update..."
  ./vendor/bin/task update "site=@${ENV_ALIAS}"
else
  echo "Running task drupal:update..."
  ./vendor/bin/task drupal:update "site=@${ENV_ALIAS}"
fi

# ---------------------------------------------------------------------------
# 10. Clear caches on all domains
# ---------------------------------------------------------------------------
echo "Clearing caches on all domains..."
HOSTNAMES=$(acli api:environments:domain-list --no-interaction "${ENV_ALIAS}" \
  | jq -r '[.[].hostname] | join(" ")')
# shellcheck disable=SC2086
acli api:environments:clear-caches --no-interaction "${ENV_ALIAS}" "${HOSTNAMES}"

echo "Deployment complete."
