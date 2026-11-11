#!/usr/bin/env bash
#
# Functional tests for tasks/snapshot.yml and scripts/snapshot-stage.sh.
#
#   usage: tests/snapshot/test-snapshot.sh
#
# Environment:
#   TASK       Path to the task binary (default: task from PATH).
#   BASELINE   Optional path to a previous tasks/snapshot.yml. When set, the
#              exported contents are also compared against it.

set -euo pipefail

REPO_ROOT=$(cd "$(dirname "$0")/../.." && pwd -P)
TASK=${TASK:-task}
BASELINE=${BASELINE:-}

WORK=$(mktemp -d)
trap 'chmod -R u+rwx "$WORK" 2>/dev/null; rm -rf "$WORK"' EXIT
# Every temporary directory created by the tasks lands here, so cleanup can be
# asserted.
export TMPDIR="$WORK/tmp"
mkdir -p "$TMPDIR"

export GIT_CONFIG_GLOBAL="$WORK/gitconfig"
export GIT_CONFIG_NOSYSTEM=1
git config --global user.name "Drainpipe Test"
git config --global user.email "test@example.com"
git config --global init.defaultBranch main
git config --global advice.detachedHead false
git config --global protocol.file.allow always

PASS=0
FAIL=0
pass() { PASS=$((PASS + 1)); echo "ok - $1"; }
fail() { FAIL=$((FAIL + 1)); echo "not ok - $1"; }
check() {
  local description=$1
  shift
  if "$@"; then pass "$description"; else fail "$description"; fi
}

# Writes a Taskfile that includes the given snapshot.yml.
write_taskfile() {
  printf 'version: "3"\nincludes:\n  snapshot: %s\n' "$1" > "$2/Taskfile.yml"
}

# A stable listing of a tree: type, permissions, symlink target, path, and a
# checksum for regular files.
manifest() {
  (
    cd "$1"
    find . -mindepth 1 -not -path './.git' -not -path './.git/*' -print0 | LC_ALL=C sort -z |
      while IFS= read -r -d '' path; do
        if [ -L "$path" ]; then
          printf 'l %s -> %s\n' "$path" "$(readlink "$path")"
        elif [ -d "$path" ]; then
          printf 'd %s\n' "$path"
        else
          if [ -x "$path" ]; then mode=x; else mode=-; fi
          printf 'f%s %s %s\n' "$mode" "$path" "$(cksum < "$path" | cut -d' ' -f1)"
        fi
      done
  )
}

extract() {
  local archive=$1 destination=$2
  mkdir -p "$destination"
  case "$archive" in
    *.zip) (cd "$destination" && unzip -q "$archive") ;;
    *) tar -xf "$archive" -C "$destination" ;;
  esac
}

# Builds the fixture project in $1 with $2 as the .drainpipeignore content.
make_project() {
  local project=$1
  mkdir -p "$project"
  cd "$project"
  git init --quiet
  write_taskfile "$REPO_ROOT/tasks/snapshot.yml" "$project"

  mkdir -p web/core web/themes/custom/theme/node_modules/pkg \
    web/libraries/kept \
    node_modules/pkg/lib .yarn/cache .ddev "dir with spaces" private
  echo "<?php // index" > web/index.php
  echo "core" > web/core/core.php
  echo "theme" > web/themes/custom/theme/theme.info.yml
  echo "dep" > web/themes/custom/theme/node_modules/pkg/index.js
  echo "kept" > web/libraries/kept/index.js
  echo "dropped" > web/libraries/dropped.js
  echo "module" > node_modules/pkg/lib/index.js
  echo "rescued?" > node_modules/pkg/rescued.js
  echo "cache" > .yarn/cache/archive.zip
  echo "ddev" > .ddev/config.yaml
  echo "spaces" > "dir with spaces/file with spaces.txt"
  echo "star" > 'web/literal*star[1].txt'
  printf '#!/bin/sh\necho hi\n' > web/run.sh
  chmod +x web/run.sh
  ln -s index.php web/link-to-index.php
  ln -s core web/link-to-core
  ln -s ../does-not-exist web/dangling
  echo "log" > web/debug.log
  echo "private" > private/secret.txt
  echo "export-ignored" > web/README.export-ignore.txt
  # shellcheck disable=SC2016 # A literal export-subst placeholder.
  echo 'Version: $Format:%s$' > web/VERSION.txt
  echo "/web/themes/custom/theme/dist" > .gitignore
  echo "nested-ignore.txt" > web/.gitignore
  cat > .gitattributes <<'EOF'
web/README.export-ignore.txt export-ignore
web/VERSION.txt export-subst
EOF
  cat > .drainpipeignore <<'EOF'
# Deployment exclusions
/.ddev
/.yarn
/private
node_modules/
!node_modules/pkg/rescued.js
/web/libraries/*
!/web/libraries/kept
*.log
EOF
  # History that a snapshot must not copy.
  dd if=/dev/urandom of=history.bin bs=1024 count=2048 2> /dev/null
  git add -A
  git commit --quiet -m "Large history"
  git rm --quiet history.bin
  git commit --quiet -m "Initial commit"
  add_untracked "$project"
}

# Content that Git does not track, so it must be recreated in a worktree.
add_untracked() {
  (
    cd "$1"
    mkdir -p web/themes/custom/theme/dist empty-dir .ddev/.mutagen-cache
    echo "built" > web/themes/custom/theme/dist/app.js
    echo "nested" > web/nested-ignore.txt
    # An unreadable file below an excluded cache directory must never be read.
    echo "secret" > .ddev/.mutagen-cache/unreadable
    chmod 000 .ddev/.mutagen-cache/unreadable .ddev/.mutagen-cache
  )
}

expected_paths() {
  cat <<'EOF'
./.gitattributes
./Taskfile.yml
./dir with spaces/file with spaces.txt
./web/VERSION.txt
./web/core/core.php
./web/dangling
./web/index.php
./web/link-to-core
./web/link-to-index.php
./web/literal*star[1].txt
./web/libraries/kept/index.js
./web/nested-ignore.txt
./web/run.sh
./web/themes/custom/theme/dist/app.js
./web/themes/custom/theme/theme.info.yml
EOF
}

# Regular files and symlinks in a tree, without the .gitignore that
# snapshot:archive has always committed from .drainpipeignore.
paths_of() {
  (cd "$1" && find . \( -type f -o -type l \) -not -path './.git/*' -not -path './.gitignore' | LC_ALL=C sort)
}

caller_state() {
  (
    cd "$1"
    git rev-parse HEAD
    git status --porcelain=v1 --ignored 2> /dev/null
    git worktree list --porcelain
    git config --local --list | LC_ALL=C sort
    git for-each-ref
    git count-objects -v | grep -v size
  )
}

assert_snapshot_contents() {
  local label=$1 tree=$2 extra=${3:-}
  check "$label: contains exactly the expected files" \
    diff <({ expected_paths; [ -z "$extra" ] || echo "$extra"; } | LC_ALL=C sort) <(paths_of "$tree")
  check "$label: executable bit preserved" test -x "$tree/web/run.sh"
  check "$label: symlink target preserved" test "$(readlink "$tree/web/link-to-index.php")" = "index.php"
  check "$label: directory symlink preserved" test "$(readlink "$tree/web/link-to-core")" = "core"
  check "$label: dangling symlink preserved" test "$(readlink "$tree/web/dangling")" = "../does-not-exist"
  check "$label: no .drainpipeignore" test ! -e "$tree/.drainpipeignore"
}

run_suite() {
  local label=$1 project=$2
  local out="$WORK/out/$label with spaces"
  local before after

  cd "$project"
  before=$(caller_state "$project")

  # snapshot:directory
  "$TASK" --silent snapshot:directory directory="$out/release" > /dev/null
  # export-ignore only applies to archives.
  assert_snapshot_contents "$label directory" "$out/release" ./web/README.export-ignore.txt
  check "$label directory: no .git" test ! -e "$out/release/.git"
  check "$label directory: empty directory preserved" test -d "$out/release/empty-dir"
  # shellcheck disable=SC2016 # Arguments are expanded by the inner shell.
  check "$label directory: refuses an existing directory" \
    bash -c '! "$0" --silent snapshot:directory directory="$1" > /dev/null 2>&1' "$TASK" "$out/release"

  # snapshot:archive in every supported format.
  local format archive
  for format in tar tar.gz tar.bz2 tar.xz zip; do
    archive="$out/archive with spaces.$format"
    "$TASK" --silent snapshot:archive o="$archive" > /dev/null
    extract "$archive" "$out/extracted-$format"
    assert_snapshot_contents "$label archive $format" "$out/extracted-$format"
    check "$label archive $format: export-ignore respected" test ! -e "$out/extracted-$format/web/README.export-ignore.txt"
    check "$label archive $format: export-subst applied" \
      grep -q "^Version: Initial commit$" "$out/extracted-$format/web/VERSION.txt"
  done

  check "$label: archive cleaned up its temporary directories" test -z "$(ls -A "$TMPDIR")"

  after=$(caller_state "$project")
  check "$label: caller HEAD, status, worktrees, config and objects unchanged" test "$before" = "$after"
}

TOTAL_START=$SECONDS

# 1. Ordinary repository.
make_project "$WORK/project"
run_suite repository "$WORK/project"

# 2. Git worktree, where .git is a file pointing at the main repository.
cd "$WORK/project"
git worktree add --quiet "$WORK/worktree" HEAD
add_untracked "$WORK/worktree"
check "worktree: .git is a file" test -f "$WORK/worktree/.git"
main_before=$(caller_state "$WORK/project")
run_suite worktree "$WORK/worktree"
check "worktree: main repository unchanged" test "$main_before" = "$(caller_state "$WORK/project")"

# 3. The rsync-less fallback.
DRAINPIPE_SNAPSHOT_NO_RSYNC=1 run_suite no-rsync "$WORK/project"

# 4. A missing .drainpipeignore keeps everything except Git metadata.
mkdir -p "$WORK/plain"
cd "$WORK/plain"
git init --quiet
write_taskfile "$REPO_ROOT/tasks/snapshot.yml" "$WORK/plain"
mkdir node_modules
echo a > a.txt
echo m > node_modules/m.js
git add -A
git commit --quiet -m init
"$TASK" --silent snapshot:directory directory="$WORK/out/plain/release" > /dev/null
check "missing .drainpipeignore: files kept" test -f "$WORK/out/plain/release/node_modules/m.js"
check "missing .drainpipeignore: no .git or .drainpipeignore" \
  test ! -e "$WORK/out/plain/release/.git" -a ! -e "$WORK/out/plain/release/.drainpipeignore"
"$TASK" --silent snapshot:archive o="$WORK/out/plain/plain.tar" > /dev/null
# shellcheck disable=SC2016 # Arguments are expanded by the inner shell.
check "missing .drainpipeignore: archive created" \
  bash -c 'tar -tf "$0" a.txt node_modules/m.js > /dev/null' "$WORK/out/plain/plain.tar"
check "missing .drainpipeignore: archive cleaned up" test -z "$(ls -A "$TMPDIR")"

# 5. A forced failure: the destination directory is not writable.
mkdir -p "$WORK/out/readonly"
chmod 555 "$WORK/out/readonly"
cd "$WORK/project"
if "$TASK" --silent snapshot:archive o="$WORK/out/readonly/fail.tar.gz" > /dev/null 2>&1; then
  fail "failure: snapshot:archive fails when it cannot write the archive"
else
  pass "failure: snapshot:archive fails when it cannot write the archive"
fi
check "failure: temporary directories removed" test -z "$(ls -A "$TMPDIR")"
chmod 755 "$WORK/out/readonly"

# 6. Optional comparison against a previous implementation.
if [ -n "$BASELINE" ]; then
  # The previous implementation copies everything, including the unreadable
  # cache, so make it readable for the baseline run only.
  chmod -R u+rwx "$WORK/project/.ddev"
  write_taskfile "$BASELINE" "$WORK/project"
  cd "$WORK/project"
  "$TASK" --silent snapshot:directory directory="$WORK/out/baseline/release" > /dev/null
  "$TASK" --silent snapshot:archive o="$WORK/out/baseline/archive.tar" > /dev/null
  write_taskfile "$REPO_ROOT/tasks/snapshot.yml" "$WORK/project"
  "$TASK" --silent snapshot:directory directory="$WORK/out/current/release" > /dev/null
  "$TASK" --silent snapshot:archive o="$WORK/out/current/archive.tar" > /dev/null
  # Taskfile.yml differs only because it points at a different snapshot.yml.
  check "baseline: snapshot:directory output identical" \
    diff <(manifest "$WORK/out/baseline/release" | grep -v ' ./Taskfile.yml ') \
    <(manifest "$WORK/out/current/release" | grep -v ' ./Taskfile.yml ')
  extract "$WORK/out/baseline/archive.tar" "$WORK/out/baseline/extracted"
  extract "$WORK/out/current/archive.tar" "$WORK/out/current/extracted"
  check "baseline: snapshot:archive contents identical" \
    diff <(manifest "$WORK/out/baseline/extracted" | grep -v ' ./Taskfile.yml ') \
    <(manifest "$WORK/out/current/extracted" | grep -v ' ./Taskfile.yml ')
fi

echo
echo "$PASS passed, $FAIL failed in $((SECONDS - TOTAL_START))s"
[ "$FAIL" -eq 0 ]
