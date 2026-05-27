---
id: 1
group: "core-implementation"
dependencies: []
status: "completed"
created: 2026-03-13
skills:
  - taskfile-yaml
  - composer
---
# Implement unified drupal:composer task with BC aliases

## Objective
Replace the two separate `composer:development` and `composer:production` tasks in `tasks/drupal.yml` with a single unified `composer` task that uses `COMPOSER_NO_DEV` passthrough, and retain the old names as thin aliases.

## Acceptance Criteria
- [ ] `tasks/drupal.yml` has a new `composer` task with three-way status check
- [ ] `composer:development` alias calls `composer` with `COMPOSER_NO_DEV: "0"`
- [ ] `composer:production` alias calls `composer` with `COMPOSER_NO_DEV: "1"`
- [ ] No duplicate `sources`/`generates`/`status` on alias tasks
- [ ] The `update` task has `deps: [composer]` added

## Technical Requirements
- The `composer` task must:
  - Export `COMPOSER_NO_DEV={{.COMPOSER_NO_DEV}}` before running `composer install --optimize-autoloader`
  - Use `sources: [composer.json, composer.lock]` and `generates: [./vendor/composer/installed.json, ./vendor/autoload.php]`
  - Three-way status check: `COMPOSER_NO_DEV="1"` → check `"dev": false`; `"0"` → check `"dev": true`; empty → only check `installed.json` exists
- Alias tasks use `cmds: [{task: composer, vars: {COMPOSER_NO_DEV: "0"/"1"}}]`
- The `update` task adds `deps: [composer]` with NO `COMPOSER_NO_DEV` variable (triggers the "empty" status check branch)

## Input Dependencies
None — this is the foundation task.

## Output Artifacts
- Modified `tasks/drupal.yml` with unified composer task, aliases, and update dependency
