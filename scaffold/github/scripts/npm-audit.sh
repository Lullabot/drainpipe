#!/usr/bin/env bash
set -euo pipefail

# Audits npm packages from their lock files and writes npm-audit-comment.md
# with a report of known vulnerabilities.
#
# Package directories are passed as arguments. When none are given they are
# read from extra.drainpipe.github.NpmAudit in composer.json. If no package
# directories are configured, no report is written.
#
# The audit runs with --package-lock-only, so no install is needed and a stale
# node_modules directory cannot affect the result. Like composer-audit.sh, this
# script reports vulnerabilities but does not fail because of them.
#
# Requires: npm, jq

OUTPUT_FILE="${NPM_AUDIT_OUTPUT:-npm-audit-comment.md}"
ROOT_DIR="$(pwd -P)"

rm -f "$OUTPUT_FILE"

PATHS=()
if [ "$#" -gt 0 ]; then
  PATHS=("$@")
elif [ -f composer.json ]; then
  TYPE=$(jq -r '.extra.drainpipe.github.NpmAudit | type' composer.json)
  case "$TYPE" in
    null) ;;
    array)
      while IFS= read -r path; do
        PATHS+=("$path")
      done < <(jq -r '.extra.drainpipe.github.NpmAudit[] | strings' composer.json)
      ;;
    *)
      echo "::error::extra.drainpipe.github.NpmAudit must be an array of package directories, got $TYPE."
      exit 1
      ;;
  esac
fi

if [ "${#PATHS[@]}" -eq 0 ]; then
  echo "No npm package directories configured in extra.drainpipe.github.NpmAudit; skipping npm audit."
  exit 0
fi

WORK_DIR="$(mktemp -d)"
trap 'rm -rf "$WORK_DIR"' EXIT

{
  echo "## npm Audit results"
  echo ""
} > "$OUTPUT_FILE"

TOTAL_VULNERABLE=0

for path in "${PATHS[@]}"; do
  path="${path%/}"
  [ -z "$path" ] && path="."
  echo "### \`$path\`" >> "$OUTPUT_FILE"
  echo "" >> "$OUTPUT_FILE"

  # Only audit directories inside the repository.
  if [ ! -d "$path" ]; then
    echo ":warning: Directory not found." >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    continue
  fi
  REAL_PATH="$(cd "$path" && pwd -P)"
  if [ "$REAL_PATH" != "$ROOT_DIR" ] && [ "${REAL_PATH#"$ROOT_DIR"/}" = "$REAL_PATH" ]; then
    echo ":warning: Directory is outside the repository; skipped." >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    continue
  fi

  if [ ! -f "$path/package-lock.json" ] && [ ! -f "$path/npm-shrinkwrap.json" ]; then
    echo ":warning: No \`package-lock.json\` or \`npm-shrinkwrap.json\` found; nothing was audited." >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    continue
  fi

  RESULT="$WORK_DIR/result.json"
  # npm audit exits non-zero when vulnerabilities are found.
  (cd "$path" && npm audit --package-lock-only --json) > "$RESULT" 2> "$WORK_DIR/stderr.log" || true

  if ! jq -e 'type == "object"' "$RESULT" > /dev/null 2>&1; then
    echo ":x: npm audit did not return a report." >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    echo '```' >> "$OUTPUT_FILE"
    tail -n 20 "$WORK_DIR/stderr.log" >> "$OUTPUT_FILE"
    echo '```' >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    continue
  fi

  if jq -e 'has("error")' "$RESULT" > /dev/null; then
    jq -r '":x: npm audit failed: " + (.error.summary // .error.code // "unknown error")' "$RESULT" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    continue
  fi

  COUNT=$(jq '.vulnerabilities // {} | length' "$RESULT")
  if [ "$COUNT" -eq 0 ]; then
    echo "**No security vulnerabilities found!**" >> "$OUTPUT_FILE"
    echo "" >> "$OUTPUT_FILE"
    continue
  fi
  TOTAL_VULNERABLE=$((TOTAL_VULNERABLE + COUNT))

  jq -r '
    .metadata.vulnerabilities as $m
    | "**\($m.total // 0) vulnerable package(s)**: \($m.critical // 0) critical, \($m.high // 0) high, \($m.moderate // 0) moderate, \($m.low // 0) low, \($m.info // 0) info",
      "",
      "| Package | Severity | Direct | Advisories | Fix available |",
      "|---------|----------|--------|------------|---------------|",
      (
        .vulnerabilities
        | to_entries
        | sort_by(
            ({"critical":0,"high":1,"moderate":2,"low":3,"info":4}[.value.severity] // 5),
            .key
          )
        | .[]
        | .value as $v
        | "| `\(.key)` | \($v.severity) | \(if $v.isDirect then "yes" else "no" end) | "
          + (
              [ $v.via[]
                | if type == "object"
                  then "[\(.title // .source | tostring | gsub("\\|"; "\\|"))](\(.url // ""))"
                  else "via `\(.)`"
                  end
              ] | unique | join("<br>")
            )
          + " | "
          + (
              if ($v.fixAvailable | type) == "object" then
                "`\($v.fixAvailable.name)@\($v.fixAvailable.version)`"
                + (if $v.fixAvailable.isSemVerMajor then " (major)" else "" end)
              elif $v.fixAvailable == true then "yes"
              else "no"
              end
            )
          + " |"
      ),
      ""
  ' "$RESULT" >> "$OUTPUT_FILE"
done

if [ "$TOTAL_VULNERABLE" -gt 0 ]; then
  echo "Run \`npm audit\` in the listed directories for details. This check reports vulnerabilities but does not fail the build." >> "$OUTPUT_FILE"
fi
