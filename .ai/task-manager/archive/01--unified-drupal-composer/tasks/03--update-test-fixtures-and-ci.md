---
id: 3
group: "downstream-updates"
dependencies: [1]
status: "completed"
created: 2026-03-13
skills:
  - taskfile-yaml
  - github-actions
---
# Update test fixtures and CI workflows

## Objective
Update the test fixture Taskfiles and CI workflow that reference the old composer task names. Keep some fixtures on old alias names for BC validation.

## Acceptance Criteria
- [ ] `tests/fixtures/drainpipe-task-upgrade/Taskfile.yml` updated to use `drupal:composer` (matches scaffold pattern)
- [ ] `tests/fixtures.drainpipe-test-build/Taskfile.yml` KEPT using `drupal:composer:production` (validates BC alias)
- [ ] `tests/fixtures/drainpipe-test-github-actions/Taskfile.yml` KEPT using `drupal:composer:production` (validates BC alias)
- [ ] `.github/workflows/TestTaskfileInstaller.yml` updated: `ddev task drupal:composer:development` → `ddev task drupal:composer`

## Technical Requirements
- `tests/fixtures/drainpipe-task-upgrade/Taskfile.yml`:
  - `build` task: change `deps: [drupal:composer:production]` to call `drupal:composer` with `COMPOSER_NO_DEV: "1"`
  - `build:dev` task: change `deps: [drupal:composer:development]` to call `drupal:composer` with `COMPOSER_NO_DEV: "0"`
- `.github/workflows/TestTaskfileInstaller.yml`:
  - Line 96: `ddev task drupal:composer:development` → `ddev task drupal:composer`
  - Line 102: `ddev task drupal:composer:development` → `ddev task drupal:composer`

## Input Dependencies
- Task 1: unified composer task must exist in `tasks/drupal.yml`

## Output Artifacts
- Modified test fixture Taskfiles
- Modified CI workflow file
