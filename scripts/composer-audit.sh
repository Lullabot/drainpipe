#!/usr/bin/env bash
set -euo pipefail

# Runs Composer audit feature and generates a comment.md file that contains
# an analysis of active and ignored vulnerabilities in project's dependencies.
#
# Requires: composer, jq

composer install --prefer-dist --no-progress
composer audit --format=json > audit-results.json || true

process_advisories() {
  local type=$1
  local output_file=$2
  local count=0

  while IFS= read -r package; do
    while IFS= read -r advisory; do
      ADVISORY_ID=$(echo "$advisory" | jq -r '.advisoryId // "unknown"')
      TITLE=$(echo "$advisory" | jq -r '.title // "No title"')
      CVE=$(echo "$advisory" | jq -r '.cve // "N/A"')
      LINK=$(echo "$advisory" | jq -r '.link // ""')
      AFFECTED=$(echo "$advisory" | jq -r '.affectedVersions // "N/A"')
      VERSION=$(echo "$advisory" | jq -r '.reportedVersion // "N/A"')

      echo "#### $package" >> "$output_file"
      echo "- **Advisory ID**: \`$ADVISORY_ID\`" >> "$output_file"
      echo "- **Title**: $TITLE" >> "$output_file"
      echo "- **CVE**: $CVE" >> "$output_file"
      echo "- **Affected Versions**: $AFFECTED" >> "$output_file"
      echo "- **Your Version**: $VERSION" >> "$output_file"
      [ -n "$LINK" ] && echo "- **Details**: $LINK" >> "$output_file"
      echo "" >> "$output_file"
      count=$((count + 1))
    done < <(jq -c ".[\"$type\"][\"$package\"][]" audit-results.json)
  done < <(jq -r ".[\"$type\"] // {} | keys[]" audit-results.json)
  echo $count
}

ADVISORY_COUNT=$(jq '.advisories // {} | length' audit-results.json)
IGNORED_COUNT=$(jq '."ignored-advisories" // {} | length' audit-results.json)

echo "## Composer Audit results" > comment.md

if [ "$ADVISORY_COUNT" -eq 0 ]; then
  echo "**No security vulnerabilities found!**" >> comment.md
  echo "" >> comment.md
else
  ACTIVE_ISSUES_FILE="/tmp/active_issues.md"
  echo "" > "$ACTIVE_ISSUES_FILE"
  ACTIVE_COUNT=$(process_advisories "advisories" "$ACTIVE_ISSUES_FILE")

  if [ "$ACTIVE_COUNT" -gt 0 ]; then
    echo "### Active Security Issues ($ACTIVE_COUNT)" >> comment.md
    echo "" >> comment.md
    echo "The following vulnerabilities require attention:" >> comment.md
    echo "" >> comment.md
    cat "$ACTIVE_ISSUES_FILE" >> comment.md
  else
    echo "### No Active Security Issues" >> comment.md
    echo "" >> comment.md
    echo "All detected vulnerabilities are currently ignored." >> comment.md
    echo "" >> comment.md
  fi
  rm -f "$ACTIVE_ISSUES_FILE"
fi

if [ "$IGNORED_COUNT" -gt 0 ]; then
  IGNORED_ISSUES_FILE="/tmp/ignored_issues.md"
  echo "" > "$IGNORED_ISSUES_FILE"
  PROCESSED_IGNORED_COUNT=$(process_advisories "ignored-advisories" "$IGNORED_ISSUES_FILE")

  if [ "$PROCESSED_IGNORED_COUNT" -gt 0 ]; then
    echo "### Ignored Security Issues ($PROCESSED_IGNORED_COUNT)" >> comment.md
    echo "" >> comment.md
    echo "<details>" >> comment.md
    echo "<summary>Click to expand ignored vulnerabilities</summary>" >> comment.md
    echo "" >> comment.md
    cat "$IGNORED_ISSUES_FILE" >> comment.md
    echo "</details>" >> comment.md
    echo "" >> comment.md
  fi
  rm -f "$IGNORED_ISSUES_FILE"
fi
