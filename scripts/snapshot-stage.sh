#!/usr/bin/env bash
#
# Copies a project directory into a staging directory for snapshot:directory
# and snapshot:archive, skipping content that the snapshot would discard anyway.
#
#   usage: snapshot-stage.sh <source> <destination>
#
# The destination must not exist or must be an empty directory.
#
# This is only an optimization: tasks/snapshot.yml still applies
# .drainpipeignore with Git (PREPARE_DIRECTORY) after staging, so this script
# only needs to be conservative, not exhaustive. It skips:
#
# - Every .git directory or file. A Git worktree or submodule has a .git
#   file pointing at the original repository, and copying it would make the
#   staging Git commands operate on the caller's repository.
# - Paths that Git itself reports as ignored by the root .drainpipeignore.
#   Git does not descend into an excluded directory, and it cannot re-include
#   files below one, so a negation such as `!node_modules/keep` never causes
#   wanted content to be skipped here. Content below an excluded directory is
#   never read, so caches with unreadable files do not break the copy.
#
# rsync is used when it is available. Without it, only top-level ignored
# entries and .git are skipped and the rest is copied with `cp -pR`.
#
# If the source has no .drainpipeignore, an empty one is created in the
# destination, matching the behavior snapshot tasks have always had.

set -euo pipefail

if [ "$#" -ne 2 ] || [ -z "$1" ] || [ -z "$2" ]; then
  echo "usage: $0 <source> <destination>" >&2
  exit 1
fi

SOURCE=$1
DESTINATION=$2

if [ ! -d "$SOURCE" ]; then
  echo "Source directory '$SOURCE' does not exist" >&2
  exit 1
fi
if [ -e "$DESTINATION" ] && [ -n "$(ls -A "$DESTINATION" 2>/dev/null || echo not-empty)" ]; then
  echo "Destination '$DESTINATION' must not exist or must be an empty directory" >&2
  exit 1
fi

# Never let an inherited Git environment point the helper repository below at
# another repository.
unset GIT_DIR GIT_WORK_TREE GIT_INDEX_FILE GIT_COMMON_DIR GIT_OBJECT_DIRECTORY GIT_ALTERNATE_OBJECT_DIRECTORIES

SOURCE=$(cd "$SOURCE" && pwd -P)

WORK_DIR=$(mktemp -d)
trap 'rm -rf "$WORK_DIR"' EXIT

IGNORED="$WORK_DIR/ignored"
: > "$IGNORED"
if [ -f "$SOURCE/.drainpipeignore" ]; then
  # Ask Git which paths .drainpipeignore excludes, using an empty throwaway
  # repository so that the caller's index, .git/info/exclude, nested
  # .gitignore files, and global excludes are all ignored, exactly as they are
  # when PREPARE_DIRECTORY runs. --directory reports an excluded directory once
  # instead of descending into it.
  git init --quiet "$WORK_DIR/repo"
  git --git-dir="$WORK_DIR/repo/.git" --work-tree="$SOURCE" \
    -c core.excludesFile=/dev/null \
    ls-files -z --others --ignored --directory \
    --exclude-from="$SOURCE/.drainpipeignore" > "$IGNORED"
fi

copy_with_rsync() {
  local filters="$WORK_DIR/filters" path
  {
    # Unanchored: matches .git directories and files at any depth.
    printf '%s\0' '.git'
    while IFS= read -r -d '' path; do
      case "${path%/}" in
        # Always stage the files PREPARE_DIRECTORY reads, even if they are
        # listed in .drainpipeignore. Git still removes them afterwards.
        .drainpipeignore|.gitattributes) continue ;;
      esac
      # rsync treats a pattern containing *, ? or [ as a wildcard, in which
      # case a backslash escapes the next character. Without those characters
      # the pattern is matched literally, including any backslashes.
      case "$path" in
        *[\*\?\[]*)
          path=${path//\\/\\\\}
          path=${path//\*/\\*}
          path=${path//\?/\\?}
          path=${path//\[/\\[}
          ;;
      esac
      # A leading slash anchors the pattern to the source root; Git's
      # trailing slash on directories is kept so it only matches directories.
      printf '/%s\0' "$path"
    done < "$IGNORED"
  } > "$filters"
  mkdir -p "$DESTINATION"
  rsync -a --from0 --exclude-from="$filters" "$SOURCE/" "$DESTINATION/"
}

copy_with_cp() {
  local path entry name skip
  local top_level=()
  while IFS= read -r -d '' path; do
    path=${path%/}
    case "$path" in
      */*|.drainpipeignore|.gitattributes) ;;
      *) top_level+=("$path") ;;
    esac
  done < "$IGNORED"

  mkdir -p "$DESTINATION"
  for entry in "$SOURCE"/* "$SOURCE"/.[!.]* "$SOURCE"/..?*; do
    # Unmatched globs stay literal; skip them unless the path really exists.
    [ -e "$entry" ] || [ -L "$entry" ] || continue
    name=${entry##*/}
    [ "$name" = ".git" ] && continue
    skip=0
    for path in ${top_level[@]+"${top_level[@]}"}; do
      if [ "$path" = "$name" ]; then
        skip=1
        break
      fi
    done
    [ "$skip" -eq 1 ] && continue
    cp -pR "$entry" "$DESTINATION/"
  done
  # Nested .git directories and files were copied along with their parents.
  find "$DESTINATION" -name .git -prune -exec rm -rf {} +
}

if [ -z "${DRAINPIPE_SNAPSHOT_NO_RSYNC:-}" ] && command -v rsync > /dev/null 2>&1; then
  copy_with_rsync
else
  echo "rsync is not available; copying the project with cp, which only skips top-level ignored paths."
  copy_with_cp
fi

if [ ! -f "$SOURCE/.drainpipeignore" ]; then
  echo ".drainpipeignore does not exist"
  touch "$DESTINATION/.drainpipeignore"
fi
