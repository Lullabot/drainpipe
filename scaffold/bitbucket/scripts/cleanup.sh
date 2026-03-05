#!/usr/bin/env bash
# cleanup.sh — Delete stale Acquia CDEs for closed Bitbucket PRs.
#
# Run via the Bitbucket scheduled custom pipeline "acquia-review-apps-cleanup".
#
# Required environment variables:
#   ACQUIA_API_KEY            Acquia API Key
#   ACQUIA_API_SECRET         Acquia API Secret
#   ACQUIA_APP_UUID           Application UUID from Acquia Cloud console
#   ACQUIA_SITE_GROUP         Application/site group name (e.g. "mysite")
#   BITBUCKET_USERNAME        Bitbucket username for API calls
#   BITBUCKET_APP_PASSWORD    Bitbucket App Password with pullrequest:read scope
#   BITBUCKET_WORKSPACE       Provided automatically by Bitbucket Pipelines
#   BITBUCKET_REPO_SLUG       Provided automatically by Bitbucket Pipelines

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "${SCRIPT_DIR}/common.sh"

# ---------------------------------------------------------------------------
# 1. Setup
# ---------------------------------------------------------------------------
drainpipe_setup_tools
drainpipe_acquia_auth

# ---------------------------------------------------------------------------
# 2. List CDEs with labels matching PR-<number>
# ---------------------------------------------------------------------------
echo "Fetching CDEs from Acquia..."
CDE_LABELS=$(acli api:environments:list --no-interaction "${ACQUIA_APP_UUID}" \
  | jq -r '.[] | select(.label | test("^PR-[0-9]+$")) | .label')

if [ -z "${CDE_LABELS}" ]; then
  echo "No PR CDEs found — nothing to clean up."
  exit 0
fi

echo "Found CDEs:"
echo "${CDE_LABELS}"

# ---------------------------------------------------------------------------
# 3. Fetch open PR IDs from Bitbucket (all pages)
# ---------------------------------------------------------------------------
echo "Fetching open PRs from Bitbucket..."
OPEN_PR_IDS=""
NEXT_URL="https://api.bitbucket.org/2.0/repositories/${BITBUCKET_WORKSPACE}/${BITBUCKET_REPO_SLUG}/pullrequests?state=OPEN&pagelen=50"

while [ -n "${NEXT_URL}" ]; do
  RESPONSE=$(curl -sf \
    -u "${BITBUCKET_USERNAME}:${BITBUCKET_APP_PASSWORD}" \
    "${NEXT_URL}")
  PAGE_IDS=$(echo "${RESPONSE}" | jq -r '.values[].id')
  OPEN_PR_IDS="${OPEN_PR_IDS}${PAGE_IDS}"$'\n'
  NEXT_URL=$(echo "${RESPONSE}" | jq -r '.next // empty')
done

echo "Open PR IDs: ${OPEN_PR_IDS:-none}"

# ---------------------------------------------------------------------------
# 4. Delete CDEs for closed PRs
# ---------------------------------------------------------------------------
while IFS= read -r CDE_LABEL; do
  # Extract numeric PR ID from label "PR-<N>"
  PR_NUM="${CDE_LABEL#PR-}"

  if echo "${OPEN_PR_IDS}" | grep -qw "${PR_NUM}"; then
    echo "PR #${PR_NUM} is still open — keeping CDE '${CDE_LABEL}'."
  else
    echo "PR #${PR_NUM} is closed — deleting CDE '${CDE_LABEL}'..."
    acli env:delete "${ACQUIA_SITE_GROUP}.pr-${PR_NUM}" --no-interaction || true
    echo "Deleted '${ACQUIA_SITE_GROUP}.pr-${PR_NUM}'."
  fi
done <<< "${CDE_LABELS}"

echo "Cleanup complete."
