# Drainpipe

Drainpipe is a composer package which provides a number of build tool helpers
for a Drupal site, including:

- Site and database updates
- Artifact packaging for deployment to a hosting provider
- Automated testing setup with support for PHPUnit and Nightwatch tests

## Installation

```
composer require lullabot/drainpipe
composer require lullabot/drainpipe-dev --dev
```

Drainpipe integrates with [DDEV](https://ddev.readthedocs.io/en/stable/), but
will only add the relevant files when DDEV is detected in the repository. Either
set DDEV up first before requiring this project, or run `composer update` if
DDEV is added later.

```
composer create-project drupal/recommended-project drupal
cd drupal
ddev config
composer require lullabot/drainpipe
composer require lullabot/drainpipe-dev --dev
ddev start
```

## Usage

Helpers are provided as [Taskfiles](https://taskfile.dev/#/). A full list of
available tasks can be shown by running `./vendor/bin/task --list` (or
`ddev task --list` if you're using DDEV).

### Drupal

|`drupal:build:prod`|Build Drupal for production usage|
|`drupal:update`|Run Drupal update tasks after deploying new code|
|`drupal:maintenance:on`|Turn on Maintenance Mode|
|`drupal:maintenance:off`|Turn off Maintenance Mode|

All the above commands can be run on a remote site by providing a [drush site
alias](https://www.drush.org/latest/site-aliases/).
e.g. `task site=@mysite.dev drupal:update`

### Snapshot

These commands will prepare a Drupal codebase for deployment.

#### `snapshot:directory`

Creates a snapshot of the current working directory

.git, .gitignore, files listed in .drainpipeignore, and .drainpipeignore
itself are not added. .drainpipeignore uses the same format as gitignore.

usage: `task snapshot:directory o=/tmp/release`

o=<file>   Write the archive to <file>

#### `snapshot:archive`

Creates a snapshot of the current working directory and exports as an archive.

`.git`, `.gitignore`, files listed in `.drainpipeignore`, and `.drainpipeignore`
itself are not added. `.drainpipeignore` uses the same format as
[`gitignore`](https://git-scm.com/docs/gitignore).

If your `.gitattributes` file contains `export-ignore` or `export-subst`, these
will be respected when exporting the archive.

usage: `task snapshot:archive o=~/archive.tar.bz2`

`o=<file>`
Write the archive to `<file>`. The compression format is inferred from the file
extension. Format options are: tar, tar.bz2, tar.gz, tar.xz, zip

### Tests

See https://github.com/Lullabot/drainpipe-dev

## Validation

Your `Taskfile.yml` can be validated with JSON Schema:
```
curl -O https://json.schemastore.org/taskfile.json
npx ajv-cli validate -s taskfile.json -d Taskfile.yml
```

See [.github/workflows/validate-taskfile.yml](`.github/workflows/validate-taskfile.yml`)
for an example of this in use.
