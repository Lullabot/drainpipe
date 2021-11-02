# Drainpipe

Drainpipe is a composer package which provides a number of build tool helpers
for a Drupal site, including:

- Site and database updates
- Artifact packaging for deployment to a hosting provider
- Automated testing setup with support for PHPUnit and Nightwatch tests

## Installation

```
composer require lullabot/drainpipe
# Includes development dependencies, but only in the `require-dev` section. This step is required for Drainpipe to provide test helpers.
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
ddev start
ddev composer require lullabot/drainpipe
ddev composer require lullabot/drainpipe-dev --dev
# Restart is required to enable the provided Selenium containers
ddev restart
```

## Usage

Build tasks are provided as [Taskfiles](https://taskfile.dev/#/). A full list of
available tasks can be shown by running `./vendor/bin/task --list` (or
`ddev task --list` if you're using DDEV).

### Running Tests

See https://github.com/Lullabot/drainpipe-dev

## Validation

Your `Taskfile.yml` can be validated with JSON Schema:
```
curl -O https://json.schemastore.org/taskfile.json
npx ajv-cli validate -s taskfile.json -d Taskfile.yml
```

See [.github/workflows/validate-taskfile.yml](`.github/workflows/validate-taskfile.yml`)
for an example of this in use.

## GitLab Integration

Add the following to `composer.json` for GitLab helpers:
```json
"extra": {
  "drainpipe": {
    "gitlab": []
  }
}
```

This will import [`scaffold/gitlab/Common.gitlab-ci.yml`](scaffold/gitlab/Common.gitlab-ci.yml),
which provides helpers that can be used in GitLab CI with [includes and
references](https://docs.gitlab.com/ee/ci/yaml/yaml_specific_features.html#reference-tags).

### Pantheon
```json
"extra": {
    "drainpipe": {
        "gitlab": ["Pantheon", "Pantheon Review Apps"]
    }
}
```

This will setup Merge Request deployment to Pantheon Multidev environments. See
[scaffold/gitlab/gitlab-ci.example.yml] for an example. You can also just
include which will give you helpers that you can include and reference for tasks
such as setting up [Terminus](https://pantheon.io/docs/terminus). See
[scaffold/gitlab/Pantheon.gitlab-ci.yml](scaffold/gitlab/Pantheon.gitlab-ci.yml).

### Composer Lock Diff
```json
"extra": {
    "drainpipe": {
        "gitlab": ["ComposerLockDiff"]
    }
}
```

Updates Merge Request descriptions with a markdown table of any changes detected
in `composer.lock` using [composer-lock-diff](https://github.com/davidrjonas/composer-lock-diff).
Requires `GITLAB_ACCESS_TOKEN` variable to be set, which is an access token with
`api` scope.

## Pantheon
