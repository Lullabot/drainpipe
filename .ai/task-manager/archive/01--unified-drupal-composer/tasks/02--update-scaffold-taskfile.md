---
id: 2
group: "downstream-updates"
dependencies: [1]
status: "completed"
created: 2026-03-13
skills:
  - taskfile-yaml
---
# Update scaffold Taskfile to use unified composer task

## Objective
Update `scaffold/Taskfile.yml` to use the new `drupal:composer` task with `COMPOSER_NO_DEV` variable instead of `drupal:composer:production` / `drupal:composer:development`.

## Acceptance Criteria
- [ ] `build` task uses `drupal:composer` with `COMPOSER_NO_DEV: "1"` instead of `drupal:composer:production`
- [ ] `build:dev` task uses `drupal:composer` with `COMPOSER_NO_DEV: "0"` instead of `drupal:composer:development`

## Technical Requirements
- The `build` task currently uses `deps: [drupal:composer:production]` — change to call `drupal:composer` with vars
- The `build:dev` task currently uses `deps: [drupal:composer:development]` — change to call `drupal:composer` with vars
- Since these are cross-namespace calls (from root Taskfile to `drupal:` namespace), the full `drupal:composer` prefix is needed

## Input Dependencies
- Task 1: unified composer task must exist in `tasks/drupal.yml`

## Output Artifacts
- Modified `scaffold/Taskfile.yml`
